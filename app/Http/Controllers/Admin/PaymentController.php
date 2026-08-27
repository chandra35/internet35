<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerPayment;
use App\Models\PopSetting;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\CustomerUnsuspendService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller implements HasMiddleware
{
    public function __construct(protected ActivityLogService $activityLog)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:invoices.view', only: ['index', 'data', 'show', 'print']),
            new Middleware('permission:invoices.edit', only: ['store', 'generateMissingPeriods']),
        ];
    }

    protected function getPopId(Request $request): ?string
    {
        $user = auth()->user();

        return $user->hasRole('superadmin')
            ? ($request->input('pop_id') ?: $request->session()->get('manage_pop_id'))
            : $user->id;
    }

    protected function ensureCustomerInPop(Customer $customer, ?string $popId): void
    {
        abort_unless($popId && $customer->pop_id === $popId, 404);
    }

    protected function unpaidInvoices(Customer $customer)
    {
        return $customer->invoices()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date');
    }

    /** List customers who have an invoice that can be settled manually. */
    public function index(Request $request)
    {
        $popId = $this->getPopId($request);
        $popUsers = auth()->user()->hasRole('superadmin')
            ? User::role('admin-pop')->orderBy('name')->get()
            : null;

        if (auth()->user()->hasRole('superadmin') && $request->has('pop_id')) {
            $request->session()->put('manage_pop_id', $request->input('pop_id'));
            $popId = $request->input('pop_id');
        }

        return view('admin.payments.index', compact('popId', 'popUsers'));
    }

    /** Server-side DataTable payload for customers with outstanding invoices. */
    public function data(Request $request)
    {
        $popId = $this->getPopId($request);
        abort_unless($popId, 422, 'Pilih POP terlebih dahulu.');

        $draw = (int) $request->input('draw', 0);
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(10, (int) $request->input('length', 20)));
        $search = trim((string) $request->input('search.value', ''));
        $period = (string) $request->input('period', now()->format('Y-m'));
        $period = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) ? $period : now()->format('Y-m');
        [$periodYear, $periodMonth] = array_map('intval', explode('-', $period));
        $unpaid = function ($query) use ($periodYear, $periodMonth) {
            $query->whereIn('status', ['pending', 'partial', 'overdue'])
                ->whereYear('period_start', $periodYear)
                ->whereMonth('period_start', $periodMonth);
        };

        $baseQuery = Customer::query()->where('pop_id', $popId)->whereHas('invoices', $unpaid);
        $recordsTotal = (clone $baseQuery)->count();
        $filteredQuery = (clone $baseQuery)->when($search !== '', function ($query) use ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('customer_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('pppoe_username', 'like', "%{$search}%");
            });
        });
        $recordsFiltered = (clone $filteredQuery)->count();
        $customers = $filteredQuery->with(['invoices' => fn ($query) => $unpaid($query)->orderBy('period_start')])
            ->orderBy('name')->skip($start)->take($length)->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $customers->map(function (Customer $customer) use ($period) {
                $firstInvoice = $customer->invoices->first();
                $outstanding = $customer->invoices->sum(fn (CustomerInvoice $invoice) => $invoice->remaining_amount);
                $dueDate = $firstInvoice?->due_date?->format('d/m/Y') ?? '—';
                $dueClass = $firstInvoice?->due_date?->isPast() ? 'text-danger font-weight-bold' : '';

                return [
                    'customer' => '<strong>' . e($customer->name) . '</strong><br><small class="text-muted">' . e($customer->customer_id) . '</small>',
                    'contact' => e($customer->phone ?: '—') . '<br><small class="text-muted">' . e($customer->pppoe_username ?: '—') . '</small>',
                    'invoices' => '<span class="badge badge-warning">' . $customer->invoices->count() . ' invoice</span>',
                    'due_date' => '<span class="' . $dueClass . '">' . e($dueDate) . '</span>',
                    'outstanding' => '<strong class="text-danger">Rp ' . number_format($outstanding, 0, ',', '.') . '</strong>',
                    'action' => '<a class="btn btn-success btn-sm payment-action" href="' . route('admin.payments.show', ['customer' => $customer, 'period' => $period]) . '"><i class="fas fa-cash-register mr-1"></i><span>Proses Bayar</span></a>',
                ];
            })->values(),
        ]);
    }

    /** Show all unpaid billing months for a single customer. */
    public function show(Request $request, Customer $customer)
    {
        $popId = $this->getPopId($request);
        $this->ensureCustomerInPop($customer, $popId);

        $invoices = $this->unpaidInvoices($customer)->get();
        $popSetting = PopSetting::where('user_id', $popId)->first();
        $missingPeriodCount = $this->missingPeriodStarts($customer)->count();
        $selectedPeriod = (string) $request->input('period', '');
        $selectedPeriod = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $selectedPeriod) ? $selectedPeriod : null;

        return view('admin.payments.show', compact('customer', 'invoices', 'popId', 'popSetting', 'missingPeriodCount', 'selectedPeriod'));
    }

    /**
     * Create only the missing monthly periods after the last real invoice.
     * This is intentionally an explicit admin action: opening a payment page
     * must never create a financial transaction by itself.
     */
    public function generateMissingPeriods(Request $request, Customer $customer)
    {
        $popId = $this->getPopId($request);
        $this->ensureCustomerInPop($customer, $popId);

        if (!$this->unpaidInvoices($customer)->exists()) {
            return back()->with('error', 'Tidak ada tunggakan yang perlu dilengkapi.');
        }

        $periodStarts = $this->missingPeriodStarts($customer);
        if ($periodStarts->isEmpty()) {
            return back()->with('info', 'Periode tagihan pelanggan sudah lengkap sampai bulan berjalan.');
        }

        $popSetting = PopSetting::where('user_id', $popId)->first();
        $dueDays = $popSetting?->invoice_due_days ?? 7;
        $created = 0;

        try {
            DB::transaction(function () use ($customer, $popId, $popSetting, $dueDays, $periodStarts, &$created) {
                $customer->loadMissing('package');
                if (!$customer->package) {
                    abort(422, 'Pelanggan belum memiliki paket internet.');
                }

                $subtotal = (float) ($customer->monthly_fee ?: $customer->package->price);
                $taxAmount = $popSetting?->ppn_enabled
                    ? $subtotal * ((float) $popSetting->ppn_percentage / 100)
                    : 0;

                foreach ($periodStarts as $periodStart) {
                    $periodEnd = $periodStart->copy()->addMonth()->subDay();
                    $exists = CustomerInvoice::where('customer_id', $customer->id)
                        ->whereDate('period_start', $periodStart)
                        ->whereDate('period_end', $periodEnd)
                        ->exists();
                    if ($exists) {
                        continue;
                    }

                    CustomerInvoice::create([
                        'customer_id' => $customer->id,
                        'pop_id' => $popId,
                        'invoice_number' => CustomerInvoice::generateInvoiceNumber($popId),
                        'invoice_date' => $periodStart,
                        'due_date' => $periodStart->copy()->addDays($dueDays),
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'items' => [[
                            'description' => 'Layanan Internet ' . $customer->package->name,
                            'amount' => $subtotal,
                        ]],
                        'subtotal' => $subtotal,
                        'discount_amount' => 0,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $subtotal + $taxAmount,
                        'paid_amount' => 0,
                        'status' => 'pending',
                        'notes' => $popSetting?->invoice_notes,
                        'created_by' => auth()->id(),
                    ]);
                    $created++;
                }
            });

            $this->activityLog->logCreate('invoices', "Melengkapi {$created} periode tagihan untuk {$customer->customer_id}");

            return back()->with('success', "{$created} periode tagihan berhasil dilengkapi.");
        } catch (\Throwable $exception) {
            Log::error("Failed to backfill invoices for {$customer->customer_id}: " . $exception->getMessage());

            return back()->with('error', 'Gagal melengkapi periode tagihan: ' . $exception->getMessage());
        }
    }

    /** Missing billing-period starts between the last invoice and this period. */
    protected function missingPeriodStarts(Customer $customer)
    {
        $lastInvoice = $customer->invoices()
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('period_start')
            ->orderByDesc('period_start')
            ->first();
        if (!$lastInvoice?->period_start) {
            return collect();
        }

        $billingDay = min(28, max(1, (int) ($customer->billing_day ?: 1)));
        $currentPeriodStart = now()->startOfMonth()->setDay($billingDay);
        if ($billingDay > now()->day) {
            $currentPeriodStart->subMonth();
        }

        $period = $lastInvoice->period_start->copy()->addMonth()->startOfDay();
        $periods = collect();
        while ($period->lessThanOrEqualTo($currentPeriodStart)) {
            $periods->push($period->copy());
            $period->addMonthNoOverflow();
        }

        return $periods;
    }

    /** Mark selected outstanding invoices as paid in one transaction. */
    public function store(Request $request, Customer $customer)
    {
        $popId = $this->getPopId($request);
        $this->ensureCustomerInPop($customer, $popId);

        $validated = $request->validate([
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['uuid', 'distinct'],
            'payment_method' => ['required', 'string', 'max:100'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            DB::transaction(function () use ($validated, $customer, $popId, &$paidCount) {
                $invoices = $this->unpaidInvoices($customer)
                    ->where('pop_id', $popId)
                    ->whereIn('id', $validated['invoice_ids'])
                    ->lockForUpdate()
                    ->get();

                if ($invoices->count() !== count($validated['invoice_ids'])) {
                    abort(422, 'Satu atau lebih invoice tidak dapat dibayar atau bukan milik pelanggan ini.');
                }

                $paidCount = 0;
                foreach ($invoices as $invoice) {
                    CustomerPayment::create([
                        'customer_id' => $customer->id,
                        'invoice_id' => $invoice->id,
                        'pop_id' => $popId,
                        'payment_number' => CustomerPayment::generatePaymentNumber($popId),
                        'amount' => $invoice->remaining_amount,
                        'payment_method' => 'manual',
                        'payment_channel' => $validated['payment_method'],
                        'status' => 'success',
                        'notes' => $validated['notes'] ?? null,
                        'paid_at' => $validated['paid_at'],
                        'verified_by' => auth()->id(),
                        'verified_at' => now(),
                        'created_by' => auth()->id(),
                    ]);

                    $invoice->update([
                        'status' => 'paid',
                        'paid_amount' => $invoice->total_amount,
                        'paid_at' => $validated['paid_at'],
                        'payment_method' => $validated['payment_method'],
                        'payment_reference' => $validated['payment_reference'] ?? null,
                    ]);
                    $paidCount++;
                }
            });

            $this->activityLog->logUpdate('payments', "Pembayaran manual {$paidCount} invoice untuk {$customer->customer_id}");
            $unsuspendMessage = $this->unsuspendWhenFullyPaid($customer);

            return redirect()->route('admin.payments.show', $customer)
                ->with('success', "Pembayaran {$paidCount} invoice berhasil dicatat." . $unsuspendMessage);
        } catch (\Throwable $exception) {
            Log::error("Batch payment failed for {$customer->customer_id}: " . $exception->getMessage());

            return back()->withInput()->with('error', 'Gagal mencatat pembayaran: ' . $exception->getMessage());
        }
    }

    /** Print any selected invoices belonging to this customer. */
    public function print(Request $request, Customer $customer)
    {
        $popId = $this->getPopId($request);
        $this->ensureCustomerInPop($customer, $popId);
        $validated = $request->validate([
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['uuid', 'distinct'],
        ]);

        $invoices = $customer->invoices()
            ->where('pop_id', $popId)
            ->whereIn('id', $validated['invoice_ids'])
            ->with(['customer.package', 'payments'])
            ->get();

        abort_if($invoices->count() !== count($validated['invoice_ids']), 422);

        foreach ($invoices as $invoice) {
            $invoice->recordPrint();
        }
        $this->activityLog->logCreate('invoices', "Mencetak {$invoices->count()} invoice dari modul pembayaran");

        $popSetting = PopSetting::where('user_id', $popId)->first();

        return view('admin.invoices.bulk-print-view', compact('invoices', 'popSetting'));
    }

    protected function unsuspendWhenFullyPaid(Customer $customer): string
    {
        if ($customer->status !== 'suspended' || $this->unpaidInvoices($customer)->exists()) {
            return '';
        }

        try {
            $result = app(CustomerUnsuspendService::class)->unsuspend($customer->fresh(['router', 'package']));
            if ($result !== 'unsuspended') {
                return " Pembayaran tercatat, tetapi isolir belum dapat dibuka di MikroTik ({$result}).";
            }

            try {
                app(NotificationService::class)->sendActivated($customer, [
                    'activate_date' => now()->format('d F Y H:i'),
                    'reason' => 'Tagihan telah dilunasi',
                ]);
            } catch (\Throwable $exception) {
                Log::warning("Activation notification failed for {$customer->customer_id}: " . $exception->getMessage());
            }

            return ' Isolir pelanggan berhasil dibuka otomatis.';
        } catch (\Throwable $exception) {
            Log::error("Batch payment unsuspend failed for {$customer->customer_id}: " . $exception->getMessage());

            return ' Pembayaran tercatat, namun pembukaan isolir perlu diperiksa.';
        }
    }
}
