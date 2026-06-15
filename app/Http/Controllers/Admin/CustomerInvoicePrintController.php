<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\PopSetting;
use App\Models\User;
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
            ->firstOrFail();

        $months = collect($validated['months'])
            ->map(fn ($m) => (int) $m)
            ->unique()
            ->sort()
            ->values();

        $invoices = CustomerInvoice::with('customer')
            ->where('pop_id', $popId)
            ->where('customer_id', $customer->id)
            ->whereYear('invoice_date', $validated['year'])
            ->whereIn(DB::raw('MONTH(invoice_date)'), $months->all())
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        if ($invoices->isEmpty()) {
            return redirect()->route('admin.invoice-customer-print.index', ['pop_id' => $popId])
                ->with('error', 'Tidak ada invoice untuk pelanggan dan periode yang dipilih.');
        }

        $foundMonths = $invoices
            ->map(fn ($inv) => (int) optional($inv->invoice_date)->format('n'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $missingMonths = $months->diff($foundMonths)->values();

        $popSetting = PopSetting::where('user_id', $popId)->first();

        return view('admin.invoice-customer-print.print', [
            'customer' => $customer,
            'invoices' => $invoices,
            'popSetting' => $popSetting,
            'selectedYear' => (int) $validated['year'],
            'selectedMonths' => $months,
            'missingMonths' => $missingMonths,
        ]);
    }
}
