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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller implements HasMiddleware
{
    protected ActivityLogService $activityLog;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:invoices.view', only: ['index', 'show', 'bulkPrintSelect', 'downloadPdf', 'printRecord']),
            new Middleware('permission:invoices.create', only: ['create', 'store', 'bulkPrint']),
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

            // Send invoice notification
            try {
                app(NotificationService::class)->sendInvoiceCreated($customer, [
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date->format('d F Y'),
                    'due_date' => $invoice->due_date->format('d F Y'),
                    'total_amount' => 'Rp ' . number_format($invoice->total_amount, 0, ',', '.'),
                    'period' => $invoice->period_start->format('d M Y') . ' - ' . $invoice->period_end->format('d M Y'),
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to send invoice notification: ' . $e->getMessage());
            }

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

            // Auto-unsuspend if customer is suspended and has no more overdue/pending invoices
            $customer = $invoice->customer;
            $unsuspendMsg = '';
            if ($customer && $customer->status === 'suspended') {
                $remainingOverdue = $customer->invoices()
                    ->whereIn('status', ['pending', 'overdue'])
                    ->where('id', '!=', $invoice->id)
                    ->exists();

                if (!$remainingOverdue) {
                    try {
                        $result = app(CustomerUnsuspendService::class)->unsuspend($customer);
                        $unsuspendMsg = $result === 'unsuspended'
                            ? ' Isolir pelanggan berhasil dibuka otomatis.'
                            : ' Isolir dibuka di sistem (Mikrotik: ' . $result . ').';

                        try {
                            app(NotificationService::class)->sendActivated($customer, [
                                'activate_date' => now()->format('d F Y H:i'),
                                'reason' => 'Tagihan telah dilunasi',
                            ]);
                        } catch (\Exception $notifErr) {
                            Log::warning("Auto-unsuspend notification failed for {$customer->customer_id}: " . $notifErr->getMessage());
                        }
                    } catch (\Exception $e) {
                        Log::error("Auto-unsuspend after markPaid failed for {$customer->customer_id}: " . $e->getMessage());
                    }
                }
            }

            return back()->with('success', 'Invoice berhasil ditandai lunas!' . $unsuspendMsg);
            
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
                
                // Send invoice notification
                try {
                    app(NotificationService::class)->sendInvoiceCreated($customer, [
                        'invoice_number' => CustomerInvoice::where('customer_id', $customer->id)->latest()->first()->invoice_number ?? '-',
                        'invoice_date' => now()->format('d F Y'),
                        'due_date' => now()->addDays($dueDays)->format('d F Y'),
                        'total_amount' => 'Rp ' . number_format($totalAmount, 0, ',', '.'),
                        'period' => $validated['period_start'] . ' - ' . $validated['period_end'],
                    ]);
                } catch (\Exception $e) {
                    Log::warning("Failed to send invoice notification to {$customer->customer_id}: " . $e->getMessage());
                }

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
        
        // Record print
        $invoice->recordPrint();
        
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

    /**
     * Show bulk print selection page
     */
    public function bulkPrintSelect(Request $request)
    {
        $popId = $this->getPopId($request);

        if (!$popId) {
            return redirect()->route('admin.invoices.index')
                ->with('error', 'Pilih POP terlebih dahulu');
        }

        $popSetting = PopSetting::where('user_id', $popId)->first();

        // Get all customers with their latest invoice for the selected period
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $customers = Customer::where('pop_id', $popId)
            ->where('status', 'active')
            ->whereNotNull('package_id')
            ->with(['package', 'invoices' => function ($q) use ($month, $year) {
                $q->whereMonth('period_start', $month)
                  ->whereYear('period_start', $year);
            }])
            ->orderBy('name')
            ->get();

        // Separate customers: those with invoices and those without
        $customersWithInvoices = $customers->filter(fn($c) => $c->invoices->isNotEmpty());
        $customersWithoutInvoices = $customers->filter(fn($c) => $c->invoices->isEmpty());

        // Get POP users for superadmin
        $popUsers = null;
        if (auth()->user()->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
        }

        return view('admin.invoices.bulk-print', compact(
            'popId', 'popSetting', 'customers', 'customersWithInvoices',
            'customersWithoutInvoices', 'month', 'year', 'popUsers'
        ));
    }

    /**
     * Generate & print selected invoices in bulk
     */
    public function bulkPrint(Request $request)
    {
        $validated = $request->validate([
            'invoice_ids' => 'required_without:customer_ids|array',
            'invoice_ids.*' => 'exists:customer_invoices,id',
            'customer_ids' => 'required_without:invoice_ids|array',
            'customer_ids.*' => 'exists:customers,id',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date',
            'output' => 'in:print,pdf',
        ]);

        $popId = $this->getPopId($request);
        $popSetting = PopSetting::where('user_id', $popId)->first();
        $output = $validated['output'] ?? 'print';

        $invoices = collect();

        // Gather existing invoices by ID
        if (!empty($validated['invoice_ids'])) {
            $invoices = CustomerInvoice::with(['customer.package', 'payments'])
                ->whereIn('id', $validated['invoice_ids'])
                ->get();
        }

        // For customers without invoices, generate them first
        if (!empty($validated['customer_ids'])) {
            $periodStart = $validated['period_start'] ?? now()->startOfMonth()->format('Y-m-d');
            $periodEnd = $validated['period_end'] ?? now()->endOfMonth()->format('Y-m-d');
            $dueDays = $popSetting?->invoice_due_days ?? 7;

            DB::beginTransaction();
            try {
                $customerIds = $validated['customer_ids'];
                $customers = Customer::with('package')
                    ->whereIn('id', $customerIds)
                    ->get();

                foreach ($customers as $customer) {
                    if (!$customer->package) continue;

                    // Check if invoice already exists for this period
                    $existingInvoice = CustomerInvoice::where('customer_id', $customer->id)
                        ->where('period_start', $periodStart)
                        ->where('period_end', $periodEnd)
                        ->first();

                    if ($existingInvoice) {
                        $invoices->push($existingInvoice);
                        continue;
                    }

                    $subtotal = $customer->package->price;
                    $taxAmount = 0;

                    if ($popSetting?->ppn_enabled) {
                        $taxAmount = $subtotal * ($popSetting->ppn_percentage / 100);
                    }

                    $totalAmount = $subtotal + $taxAmount;

                    $invoice = CustomerInvoice::create([
                        'customer_id' => $customer->id,
                        'pop_id' => $popId,
                        'invoice_number' => CustomerInvoice::generateInvoiceNumber($popId),
                        'invoice_date' => now(),
                        'due_date' => now()->addDays($dueDays),
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
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

                    $invoice->load(['customer.package', 'payments']);
                    $invoices->push($invoice);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Bulk print - failed to generate invoices: ' . $e->getMessage());
                return back()->with('error', 'Gagal membuat invoice: ' . $e->getMessage());
            }
        }

        if ($invoices->isEmpty()) {
            return back()->with('error', 'Tidak ada invoice yang dipilih');
        }

        // Record print for all invoices
        foreach ($invoices as $inv) {
            $inv->recordPrint();
        }

        $this->activityLog->logCreate('invoices', "Bulk printed {$invoices->count()} invoices");

        if ($output === 'pdf') {
            return $this->generateBulkPdf($invoices, $popSetting);
        }

        // For print output, return a multi-invoice print view
        return view('admin.invoices.bulk-print-view', compact('invoices', 'popSetting'));
    }

    /**
     * Generate a combined PDF for multiple invoices
     */
    protected function generateBulkPdf($invoices, $popSetting)
    {
        $pdf = Pdf::loadView('admin.invoices.bulk-print-pdf', [
            'invoices' => $invoices,
            'popSetting' => $popSetting,
        ])->setPaper('a4');

        // Save PDF to storage
        $filename = 'invoices/bulk_' . now()->format('Ymd_His') . '_' . $invoices->count() . 'inv.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        // Update pdf_path for all invoices
        foreach ($invoices as $inv) {
            $inv->update(['pdf_path' => $filename]);
        }

        return $pdf->download('invoices_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Download a previously generated PDF invoice
     */
    public function downloadPdf(CustomerInvoice $invoice)
    {
        // Generate fresh PDF for this single invoice
        $invoice->load(['customer.package', 'payments']);
        $popSetting = PopSetting::where('user_id', $invoice->pop_id)->first();

        $pdf = Pdf::loadView('admin.invoices.bulk-print-pdf', [
            'invoices' => collect([$invoice]),
            'popSetting' => $popSetting,
        ])->setOptions(['font_subsetting' => false])->setPaper('a4');

        // Save path
        $filename = 'invoices/' . $invoice->invoice_number . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        $invoice->update(['pdf_path' => $filename]);
        $invoice->recordPrint();

        return $pdf->stream($invoice->invoice_number . '.pdf');
    }

    /**
     * Print invoice & record it (updated single print)
     */
    public function printRecord(CustomerInvoice $invoice)
    {
        $invoice->load(['customer.package', 'payments']);
        $popSetting = PopSetting::where('user_id', $invoice->pop_id)->first();

        // Record this print action
        $invoice->recordPrint();

        return view('admin.invoices.print', compact('invoice', 'popSetting'));
    }
}
