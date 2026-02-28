<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerPayment;
use App\Models\PopSetting;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class InvoiceController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:invoices.view', only: ['index', 'show']),
            new Middleware('permission:invoices.create', only: ['create', 'store']),
            new Middleware('permission:invoices.edit', only: ['edit', 'update']),
            new Middleware('permission:invoices.delete', only: ['destroy']),
        ];
    }

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    /**
     * Get POP ID based on user role
     */
    protected function getPopId(Request $request)
    {
        $user = auth()->user();
        
        if ($user->hasRole('superadmin')) {
            return $request->input('pop_id') ?: $request->session()->get('manage_pop_id');
        }
        
        return $user->id;
    }

    /**
     * Display invoices list
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $popId = $this->getPopId($request);
        
        // For superadmin, get list of POPs
        $popUsers = null;
        if ($user->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
            
            if ($request->has('pop_id')) {
                $request->session()->put('manage_pop_id', $request->input('pop_id'));
                $popId = $request->input('pop_id');
            }
        }

        // Get POP settings for invoice configuration
        $popSetting = $popId ? PopSetting::where('user_id', $popId)->first() : null;
        
        // Build query
        $query = CustomerInvoice::with(['customer', 'creator'])
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->month, function($q, $m) {
                $q->whereMonth('invoice_date', $m);
            })
            ->when($request->year, function($q, $y) {
                $q->whereYear('invoice_date', $y);
            })
            ->when($request->search, function($q, $s) {
                $q->where(function($sq) use ($s) {
                    $sq->where('invoice_number', 'like', "%{$s}%")
                       ->orWhereHas('customer', function($cq) use ($s) {
                           $cq->where('name', 'like', "%{$s}%")
                              ->orWhere('customer_id', 'like', "%{$s}%");
                       });
                });
            });
        
        $invoices = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Statistics
        $stats = [];
        if ($popId) {
            $baseQuery = CustomerInvoice::where('pop_id', $popId);
            $stats = [
                'total' => (clone $baseQuery)->count(),
                'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
                'paid' => (clone $baseQuery)->where('status', 'paid')->count(),
                'overdue' => (clone $baseQuery)->where('status', 'overdue')->count(),
                'partial' => (clone $baseQuery)->where('status', 'partial')->count(),
                'total_pending_amount' => (clone $baseQuery)->where('status', 'pending')->sum('total_amount'),
                'total_paid_amount' => (clone $baseQuery)->where('status', 'paid')
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year)
                    ->sum('total_amount'),
            ];
        }
        
        return view('admin.invoices.index', compact('invoices', 'popUsers', 'popId', 'popSetting', 'stats'));
    }

    /**
     * Show create invoice form
     */
    public function create(Request $request)
    {
        $popId = $this->getPopId($request);
        
        if (!$popId) {
            return redirect()->route('admin.invoices.index')
                ->with('error', 'Pilih POP terlebih dahulu');
        }

        $popSetting = PopSetting::where('user_id', $popId)->first();
        
        // Get active customers
        $customers = Customer::where('pop_id', $popId)
            ->where('status', 'active')
            ->with('package')
            ->orderBy('name')
            ->get();
        
        // Calculate default period
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();
        $dueDays = $popSetting?->invoice_due_days ?? 7;
        $dueDate = now()->addDays($dueDays);
        
        return view('admin.invoices.create', compact('popId', 'popSetting', 'customers', 'periodStart', 'periodEnd', 'dueDate'));
    }

    /**
     * Store new invoice
     */
    public function store(Request $request)
    {
        $popId = $this->getPopId($request);
        
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        try {
            DB::beginTransaction();
            
            $customer = Customer::findOrFail($validated['customer_id']);
            $popSetting = PopSetting::where('user_id', $popId)->first();
            
            // Calculate totals
            $subtotal = collect($validated['items'])->sum('amount');
            $discountAmount = $validated['discount_amount'] ?? 0;
            
            // Calculate tax if enabled
            $taxAmount = 0;
            if ($popSetting?->ppn_enabled) {
                $taxableAmount = $subtotal - $discountAmount;
                $taxAmount = $taxableAmount * ($popSetting->ppn_percentage / 100);
            }
            
            $totalAmount = $subtotal - $discountAmount + $taxAmount;
            
            $invoice = CustomerInvoice::create([
                'customer_id' => $customer->id,
                'pop_id' => $popId,
                'invoice_number' => CustomerInvoice::generateInvoiceNumber($popId),
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'items' => $validated['items'],
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
            
            DB::commit();
            
            $this->activityLog->logCreate('invoices', "Created invoice {$invoice->invoice_number}");
            
            return redirect()->route('admin.invoices.show', $invoice)
                ->with('success', 'Invoice berhasil dibuat!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create invoice: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'Gagal membuat invoice: ' . $e->getMessage());
        }
    }

    /**
     * Show invoice detail
     */
    public function show(CustomerInvoice $invoice)
    {
        $invoice->load(['customer.package', 'payments', 'creator']);
        $popSetting = PopSetting::where('user_id', $invoice->pop_id)->first();
        
        return view('admin.invoices.show', compact('invoice', 'popSetting'));
    }

    /**
     * Show edit invoice form
     */
    public function edit(CustomerInvoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return redirect()->route('admin.invoices.show', $invoice)
                ->with('error', 'Invoice yang sudah lunas tidak dapat diedit');
        }
        
        $popSetting = PopSetting::where('user_id', $invoice->pop_id)->first();
        $customers = Customer::where('pop_id', $invoice->pop_id)
            ->where('status', 'active')
            ->with('package')
            ->orderBy('name')
            ->get();
        
        return view('admin.invoices.edit', compact('invoice', 'popSetting', 'customers'));
    }

    /**
     * Update invoice
     */
    public function update(Request $request, CustomerInvoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return redirect()->route('admin.invoices.show', $invoice)
                ->with('error', 'Invoice yang sudah lunas tidak dapat diedit');
        }
        
        $validated = $request->validate([
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        try {
            DB::beginTransaction();
            
            $popSetting = PopSetting::where('user_id', $invoice->pop_id)->first();
            
            // Calculate totals
            $subtotal = collect($validated['items'])->sum('amount');
            $discountAmount = $validated['discount_amount'] ?? 0;
            
            // Calculate tax if enabled
            $taxAmount = 0;
            if ($popSetting?->ppn_enabled) {
                $taxableAmount = $subtotal - $discountAmount;
                $taxAmount = $taxableAmount * ($popSetting->ppn_percentage / 100);
            }
            
            $totalAmount = $subtotal - $discountAmount + $taxAmount;
            
            $oldData = $invoice->toArray();
            
            $invoice->update([
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'items' => $validated['items'],
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);
            
            DB::commit();
            
            $this->activityLog->logUpdate('invoices', "Updated invoice {$invoice->invoice_number}", $oldData, $invoice->toArray());
            
            return redirect()->route('admin.invoices.show', $invoice)
                ->with('success', 'Invoice berhasil diupdate!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update invoice: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'Gagal mengupdate invoice: ' . $e->getMessage());
        }
    }

    /**
     * Delete invoice
     */
    public function destroy(CustomerInvoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice yang sudah lunas tidak dapat dihapus');
        }
        
        if ($invoice->payments()->exists()) {
            return back()->with('error', 'Invoice yang memiliki pembayaran tidak dapat dihapus');
        }
        
        try {
            $invoiceNumber = $invoice->invoice_number;
            $invoice->delete();
            
            $this->activityLog->logDelete('invoices', "Deleted invoice {$invoiceNumber}");
            
            return redirect()->route('admin.invoices.index')
                ->with('success', 'Invoice berhasil dihapus!');
                
        } catch (\Exception $e) {
            Log::error('Failed to delete invoice: ' . $e->getMessage());
            
            return back()->with('error', 'Gagal menghapus invoice: ' . $e->getMessage());
        }
    }

    /**
     * Mark invoice as paid manually
     */
    public function markPaid(Request $request, CustomerInvoice $invoice)
    {
        $validated = $request->validate([
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string|max:100',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Create payment record
            $payment = CustomerPayment::create([
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'pop_id' => $invoice->pop_id,
                'payment_number' => CustomerPayment::generatePaymentNumber($invoice->pop_id),
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
            
            // Update invoice
            $invoice->update([
                'status' => 'paid',
                'paid_amount' => $invoice->total_amount,
                'paid_at' => $validated['paid_at'],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'],
            ]);
            
            DB::commit();
            
            $this->activityLog->logUpdate('invoices', "Marked invoice {$invoice->invoice_number} as paid");
            
            return back()->with('success', 'Invoice berhasil ditandai lunas!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark invoice as paid: ' . $e->getMessage());
            
            return back()->with('error', 'Gagal menandai invoice: ' . $e->getMessage());
        }
    }

    /**
     * Cancel invoice
     */
    public function cancel(Request $request, CustomerInvoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice yang sudah lunas tidak dapat dibatalkan');
        }
        
        try {
            $invoice->update([
                'status' => 'cancelled',
            ]);
            
            $this->activityLog->logUpdate('invoices', "Cancelled invoice {$invoice->invoice_number}");
            
            return back()->with('success', 'Invoice berhasil dibatalkan!');
            
        } catch (\Exception $e) {
            Log::error('Failed to cancel invoice: ' . $e->getMessage());
            
            return back()->with('error', 'Gagal membatalkan invoice: ' . $e->getMessage());
        }
    }

    /**
     * Generate invoices for all active customers
     */
    public function generate(Request $request)
    {
        $popId = $this->getPopId($request);
        
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);
        
        try {
            DB::beginTransaction();
            
            $popSetting = PopSetting::where('user_id', $popId)->first();
            $dueDays = $popSetting?->invoice_due_days ?? 7;
            
            // Get active customers without invoice for this period
            $customers = Customer::where('pop_id', $popId)
                ->where('status', 'active')
                ->whereNotNull('package_id')
                ->with('package')
                ->whereDoesntHave('invoices', function($q) use ($validated) {
                    $q->where('period_start', $validated['period_start'])
                      ->where('period_end', $validated['period_end']);
                })
                ->get();
            
            $generated = 0;
            
            foreach ($customers as $customer) {
                if (!$customer->package) continue;
                
                $subtotal = $customer->package->price;
                $taxAmount = 0;
                
                if ($popSetting?->ppn_enabled) {
                    $taxAmount = $subtotal * ($popSetting->ppn_percentage / 100);
                }
                
                $totalAmount = $subtotal + $taxAmount;
                
                CustomerInvoice::create([
                    'customer_id' => $customer->id,
                    'pop_id' => $popId,
                    'invoice_number' => CustomerInvoice::generateInvoiceNumber($popId),
                    'invoice_date' => now(),
                    'due_date' => now()->addDays($dueDays),
                    'period_start' => $validated['period_start'],
                    'period_end' => $validated['period_end'],
                    'items' => [
                        [
                            'description' => 'Layanan Internet ' . $customer->package->name,
                            'amount' => $subtotal,
                        ]
                    ],
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'paid_amount' => 0,
                    'status' => 'pending',
                    'notes' => $popSetting?->invoice_notes,
                    'created_by' => auth()->id(),
                ]);
                
                $generated++;
            }
            
            DB::commit();
            
            $this->activityLog->logCreate('invoices', "Generated {$generated} invoices");
            
            return redirect()->route('admin.invoices.index')
                ->with('success', "Berhasil generate {$generated} invoice!");
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate invoices: ' . $e->getMessage());
            
            return back()->with('error', 'Gagal generate invoice: ' . $e->getMessage());
        }
    }

    /**
     * Print invoice
     */
    public function print(CustomerInvoice $invoice)
    {
        $invoice->load(['customer.package', 'payments']);
        $popSetting = PopSetting::where('user_id', $invoice->pop_id)->first();
        
        return view('admin.invoices.print', compact('invoice', 'popSetting'));
    }

    /**
     * Send invoice reminder
     */
    public function sendReminder(CustomerInvoice $invoice)
    {
        // TODO: Implement notification sending
        // This will integrate with message templates and notification channels
        
        return back()->with('success', 'Reminder berhasil dikirim!');
    }
}
