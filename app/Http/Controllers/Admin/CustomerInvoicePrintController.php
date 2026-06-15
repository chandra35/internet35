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
    public static function middleware(): array
    {
        return [
            new Middleware('permission:invoices.view', only: ['index', 'print']),
        ];
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
        ]);

        $customer = Customer::where('id', $validated['customer_id'])
            ->where('pop_id', $popId)
            ->with('package')
            ->firstOrFail();

        $popSetting = PopSetting::where('user_id', $popId)->first();
        $dueDays = $popSetting?->invoice_due_days ?? 7;

        $months = collect($validated['months'])
            ->map(fn ($m) => (int) $m)
            ->unique()
            ->sort()
            ->values();

        DB::beginTransaction();
        try {
            $printRows = $months->map(function (int $month) use ($validated, $popId, $customer, $popSetting, $dueDays) {
                $periodStart = Carbon::create((int) $validated['year'], $month, 1)->startOfMonth();
                $periodEnd = (clone $periodStart)->endOfMonth();

                $invoice = CustomerInvoice::where('pop_id', $popId)
                    ->where('customer_id', $customer->id)
                    ->whereDate('period_start', $periodStart->toDateString())
                    ->whereDate('period_end', $periodEnd->toDateString())
                    ->orderBy('created_at')
                    ->first();

                if (!$invoice) {
                    if (!$customer->package) {
                        abort(422, 'Pelanggan tidak memiliki paket aktif untuk generate invoice.');
                    }

                    $subtotal = (float) $customer->package->price;
                    $taxAmount = 0.0;

                    if ($popSetting?->ppn_enabled) {
                        $taxAmount = $subtotal * ((float) $popSetting->ppn_percentage / 100);
                    }

                    $totalAmount = $subtotal + $taxAmount;

                    $invoice = CustomerInvoice::create([
                        'customer_id' => $customer->id,
                        'pop_id' => $popId,
                        'invoice_number' => CustomerInvoice::generateInvoiceNumber($popId),
                        'invoice_date' => now(),
                        'due_date' => now()->addDays($dueDays),
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
