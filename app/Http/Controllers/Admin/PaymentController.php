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
            new Middleware('permission:invoices.edit', only: ['store']),
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
        $unpaid = fn ($query) => $query->whereIn('status', ['pending', 'partial', 'overdue']);

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
            'data' => $customers->map(function (Customer $customer) {
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
                    'action' => '<a class="btn btn-success btn-sm payment-action" href="' . route('admin.payments.show', $customer) . '"><i class="fas fa-cash-register mr-1"></i><span>Proses Bayar</span></a>',
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

        return view('admin.payments.show', compact('customer', 'invoices', 'popId', 'popSetting'));
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
