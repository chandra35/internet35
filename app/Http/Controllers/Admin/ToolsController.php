<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerPayment;
use App\Models\NotificationLog;
use App\Models\ScheduledTask;
use App\Models\ScheduledTaskLog;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ToolsController extends Controller
{
    protected ActivityLogService $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    protected function getPopId(): ?string
    {
        $user = auth()->user();
        return $user->hasRole('superadmin') ? null : $user->id;
    }

    /**
     * Show tools / data management page
     */
    public function index()
    {
        $popId = $this->getPopId();
        $user  = auth()->user();

        $customerIds = Customer::withTrashed()
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->pluck('id');

        $counts = [
            'invoices'          => CustomerInvoice::withTrashed()->whereIn('customer_id', $customerIds)->count(),
            'payments'          => CustomerPayment::withTrashed()->whereIn('customer_id', $customerIds)->count(),
            'notification_logs' => NotificationLog::when($popId, fn($q) => $q->where('pop_id', $popId))->count(),
            'activity_logs'     => ActivityLog::withTrashed()->where('user_id', $user->id)->count(),
            'customers_billing' => Customer::withTrashed()
                ->when($popId, fn($q) => $q->where('pop_id', $popId))
                ->whereNotNull('active_until')
                ->count(),
            'scheduler_logs'    => ScheduledTaskLog::whereHas('task', function ($q) use ($popId) {
                $q->when($popId, fn($taskQuery) => $taskQuery->where('pop_id', $popId));
            })->count(),
        ];

        return view('admin.tools.index', compact('counts'));
    }

    /**
     * Hapus semua invoice + pembayaran terkait
     */
    public function clearInvoices(Request $request)
    {
        $request->validate(['confirm_text' => 'required|in:HAPUS DATA']);

        $popId = $this->getPopId();
        $customerIds = Customer::withTrashed()
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->pluck('id');

        DB::transaction(function () use ($customerIds) {
            CustomerPayment::withTrashed()->whereIn('customer_id', $customerIds)->forceDelete();
            CustomerInvoice::withTrashed()->whereIn('customer_id', $customerIds)->forceDelete();
        });

        $scope = $popId ? '(POP sendiri)' : '(semua POP)';
        $this->activityLog->log('purge', 'tools', "Menghapus semua invoice & pembayaran $scope");

        return back()->with('success', 'Semua data invoice dan pembayaran berhasil dihapus.');
    }

    /**
     * Hapus riwayat pembayaran saja (invoice tetap ada)
     */
    public function clearPayments(Request $request)
    {
        $request->validate(['confirm_text' => 'required|in:HAPUS DATA']);

        $popId = $this->getPopId();
        $customerIds = Customer::withTrashed()
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->pluck('id');

        CustomerPayment::withTrashed()->whereIn('customer_id', $customerIds)->forceDelete();

        $scope = $popId ? '(POP sendiri)' : '(semua POP)';
        $this->activityLog->log('purge', 'tools', "Menghapus semua riwayat pembayaran $scope");

        return back()->with('success', 'Semua riwayat pembayaran berhasil dihapus.');
    }

    /**
     * Hapus semua log notifikasi
     */
    public function clearNotificationLogs(Request $request)
    {
        $request->validate(['confirm_text' => 'required|in:HAPUS DATA']);

        $popId = $this->getPopId();
        NotificationLog::when($popId, fn($q) => $q->where('pop_id', $popId))->delete();

        $scope = $popId ? '(POP sendiri)' : '(semua POP)';
        $this->activityLog->log('purge', 'tools', "Menghapus semua log notifikasi $scope");

        return back()->with('success', 'Semua log notifikasi berhasil dihapus.');
    }

    /**
     * Hapus semua activity log milik user ini
     */
    public function clearActivityLogs(Request $request)
    {
        $request->validate(['confirm_text' => 'required|in:HAPUS DATA']);

        $user = auth()->user();
        ActivityLog::withTrashed()->where('user_id', $user->id)->forceDelete();

        // Tulis ulang satu log untuk rekam tindakan ini
        $this->activityLog->log('purge', 'tools', 'Menghapus semua activity log akun sendiri');

        return back()->with('success', 'Semua activity log berhasil dihapus.');
    }

    /**
     * Reset status billing semua pelanggan ke aktif
     */
    public function resetBilling(Request $request)
    {
        $request->validate(['confirm_text' => 'required|in:HAPUS DATA']);

        $popId = $this->getPopId();

        Customer::withTrashed()
            ->when($popId, fn($q) => $q->where('pop_id', $popId))
            ->update([
                'active_until' => null,
                'due_date'     => null,
                'status'       => 'active',
            ]);

        $scope = $popId ? '(POP sendiri)' : '(semua POP)';
        $this->activityLog->log('purge', 'tools', "Reset status billing semua pelanggan ke aktif $scope");

        return back()->with('success', 'Status billing semua pelanggan berhasil direset ke aktif.');
    }

    /**
     * Reset transactional data before first operational use.
     *
     * Basic master data and the current MikroTik service state are deliberately
     * retained. This action is restricted to superadmin because it resets
     * global scheduler counters as well as billing records.
     */
    public function resetTransactionalData(Request $request)
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403);

        $request->validate(['confirm_text' => 'required|in:RESET TRANSAKSI']);

        $customerIds = Customer::withTrashed()->pluck('id');
        $taskIds = ScheduledTask::pluck('id');

        DB::transaction(function () use ($customerIds, $taskIds) {
            CustomerPayment::withTrashed()->whereIn('customer_id', $customerIds)->forceDelete();
            CustomerInvoice::withTrashed()->whereIn('customer_id', $customerIds)->forceDelete();
            NotificationLog::query()->delete();
            ScheduledTaskLog::whereIn('scheduled_task_id', $taskIds)->delete();

            ScheduledTask::whereIn('id', $taskIds)->update([
                'last_run_at' => null,
                'last_status' => 'pending',
                'last_output' => null,
                'run_count' => 0,
                'failure_count' => 0,
                'next_run_at' => now(),
            ]);

            // Keep customers, packages, network links, and current service
            // status intact. Only billing date markers are reset.
            Customer::withTrashed()->update([
                'active_until' => null,
                'due_date' => null,
            ]);
        });

        $this->activityLog->log('purge', 'tools', 'Reset data transaksi awal: invoice, pembayaran, log notifikasi, dan counter scheduler');

        return back()->with('success', 'Data transaksi dan counter berhasil direset. Data pelanggan, jaringan, dan status layanan tidak diubah.');
    }
}
