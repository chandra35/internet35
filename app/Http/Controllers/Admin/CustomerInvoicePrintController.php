<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\PopSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerInvoicePrintController extends Controller implements HasMiddleware
{
    /**
     * Package prices are stored as final prices; split base and tax when PPN is enabled.
     */
    private function calculateFromPackagePrice(float $packagePrice, ?PopSetting $popSetting): array
    {
        $packagePrice = max(0, $packagePrice);

        if (!$popSetting?->ppn_enabled) {
            return [
                'subtotal' => round($packagePrice, 2),
                'tax_amount' => 0.0,
                'total_amount' => round($packagePrice, 2),
            ];
        }

        $rate = (float) ($popSetting->ppn_percentage ?? 0);
        if ($rate <= 0) {
            return [
                'subtotal' => round($packagePrice, 2),
                'tax_amount' => 0.0,
                'total_amount' => round($packagePrice, 2),
            ];
        }

        $divisor = 1 + ($rate / 100);
        $subtotal = $packagePrice / $divisor;
        $taxAmount = $packagePrice - $subtotal;

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($packagePrice, 2),
        ];
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:invoices.view', only: ['index', 'print']),
        ];
    }

    /**
     * Generate random invoice number for this feature so numbers are not sequential.
     * Keeps uniqueness across active + soft-deleted invoices.
     */
    private function generateRandomInvoiceNumber(string $popId, Carbon $periodStart, ?PopSetting $popSetting): string
    {
        $prefix = rtrim($popSetting?->invoice_prefix ?? 'INV', '-');
        $ym = $periodStart->format('Ym');

        for ($i = 0; $i < 100; $i++) {
            $random = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $invoiceNumber = $prefix . '-' . $ym . '-' . $random;

            $exists = CustomerInvoice::withTrashed()
                ->where('invoice_number', $invoiceNumber)
                ->exists();

            if (!$exists) {
                return $invoiceNumber;
            }
        }

        throw new \RuntimeException('Gagal membuat nomor invoice acak yang unik.');
    }

    protected function getPopId(Request $request)
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        if ($user->hasRole('superadmin')) {
            if ($request->has('pop_id')) {
                $request->session()->put('manage_pop_id', $request->input('pop_id'));
                return $request->input('pop_id') ?: null;
            }
            return $request->session()->get('manage_pop_id');
        }

        return $user->id;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */
        $popId = $this->getPopId($request);

        $popUsers = null;
        if ($user->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
        }

        $customers = collect();
        if ($popId) {
            $customers = Customer::where('pop_id', $popId)
                ->whereHas('invoices')
                ->orderBy('name')
                ->get(['id', 'name', 'customer_id']);
        }

        $currentYear = (int) now()->year;

        return view('admin.invoice-customer-print.index', compact('popUsers', 'popId', 'customers', 'currentYear'));
    }

    public function print(Request $request)
    {
        $popId = $this->getPopId($request);

        if (!$popId) {
            return redirect()->route('admin.invoice-customer-print.index')
                ->with('error', 'Pilih POP terlebih dahulu.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'year' => 'required|integer|min:2020|max:2100',
            'months' => 'required|array|min:1',
            'months.*' => 'required|integer|min:1|max:12',
            'regenerate' => 'nullable|boolean',
        ]);

        $customer = Customer::where('id', $validated['customer_id'])
            ->where('pop_id', $popId)
            ->with('package')
            ->firstOrFail();

        $popSetting = PopSetting::where('user_id', $popId)->first();
        $regenerate = (bool) ($validated['regenerate'] ?? false);

        $months = collect($validated['months'])
            ->map(fn ($m) => (int) $m)
            ->unique()
            ->sort()
            ->values();

        DB::beginTransaction();
        try {
            $printRows = $months->map(function (int $month) use ($validated, $popId, $customer, $popSetting, $regenerate) {
                $periodStart = Carbon::create((int) $validated['year'], $month, 1)->startOfMonth();
                $periodEnd = (clone $periodStart)->endOfMonth();

                $invoice = CustomerInvoice::where('pop_id', $popId)
                    ->where('customer_id', $customer->id)
                    ->whereDate('period_start', $periodStart->toDateString())
                    ->whereDate('period_end', $periodEnd->toDateString())
                    ->orderBy('created_at')
                    ->first();

                if ($regenerate && $invoice) {
                    if ($invoice->status === 'paid') {
                        throw new \RuntimeException('Tidak bisa regenerate invoice yang sudah lunas (' . $invoice->invoice_number . ').');
                    }

                    $invoice->delete();
                    $invoice = null;
                }

                if (!$invoice) {
                    if (!$customer->package) {
                        abort(422, 'Pelanggan tidak memiliki paket aktif untuk generate invoice.');
                    }

                    $invoiceDay = random_int(1, 10);
                    $dueDay = random_int(10, 15);
                    $invoiceDate = Carbon::create((int) $validated['year'], $month, $invoiceDay)->startOfDay();
                    $dueDate = Carbon::create((int) $validated['year'], $month, $dueDay)->startOfDay();

                    $amounts = $this->calculateFromPackagePrice((float) $customer->package->price, $popSetting);
                    $subtotal = $amounts['subtotal'];
                    $taxAmount = $amounts['tax_amount'];
                    $totalAmount = $amounts['total_amount'];

                    $invoice = CustomerInvoice::create([
                        'customer_id' => $customer->id,
                        'pop_id' => $popId,
                        'invoice_number' => $this->generateRandomInvoiceNumber($popId, $periodStart, $popSetting),
                        'invoice_date' => $invoiceDate,
                        'due_date' => $dueDate,
                        'period_start' => $periodStart->toDateString(),
                        'period_end' => $periodEnd->toDateString(),
                        'items' => [
                            [
                                'description' => 'Layanan Internet ' . $customer->package->name,
                                'amount' => $subtotal,
                            ],
                        ],
                        'subtotal' => $subtotal,
                        'discount_amount' => 0,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $totalAmount,
                        'paid_amount' => 0,
                        'status' => 'pending',
                        'notes' => $popSetting?->invoice_notes,
                        'created_by' => Auth::id(),
                    ]);
                }

                return [
                    'month' => $month,
                    'invoice' => $invoice->loadMissing('customer'),
                ];
            });

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('admin.invoice-customer-print.index', ['pop_id' => $popId])
                ->with('error', 'Gagal menyiapkan invoice cetak: ' . $e->getMessage());
        }

        return view('admin.invoice-customer-print.print', [
            'customer' => $customer,
            'printRows' => $printRows,
            'popSetting' => $popSetting,
            'selectedYear' => (int) $validated['year'],
            'selectedMonths' => $months,
        ]);
    }
}
