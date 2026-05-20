<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\MessageTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class NotificationLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:notification-settings.view', only: ['index', 'show']),
            new Middleware('permission:notification-settings.edit', only: ['resend', 'destroy']),
        ];
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        // Get POP context
        $popUsers = null;
        if ($user->hasRole('superadmin')) {
            $popUsers = User::role('admin-pop')->orderBy('name')->get();
            if ($request->has('pop_id')) {
                $request->session()->put('manage_pop_id', $request->input('pop_id'));
                $popId = $request->input('pop_id') ?: null;
            } else {
                $popId = $request->session()->get('manage_pop_id');
            }
        } else {
            $popId = $user->hasRole('admin') ? $request->get('user_id', $user->id) : $user->id;
        }

        $query = NotificationLog::with('customer')->latest();

        if ($popId) {
            $query->where('pop_id', $popId);
        } else {
            // Superadmin without POP selected: show nothing
            $query->whereRaw('1=0');
        }

        // Filters
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('template_code')) {
            $query->where('template_code', $request->template_code);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recipient', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(25)->appends($request->query());
        $stats = $popId ? NotificationLog::statsForPop($popId) : [];
        $templateCodes = MessageTemplate::templateCodes();

        return view('admin.notification-logs.index', compact('logs', 'stats', 'templateCodes', 'popId', 'popUsers'));
    }

    public function show(NotificationLog $notificationLog)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && $notificationLog->pop_id !== $user->id) {
            abort(403);
        }

        $notificationLog->load('customer');

        return response()->json([
            'success' => true,
            'log' => [
                'id' => $notificationLog->id,
                'channel' => $notificationLog->channel,
                'channel_label' => $notificationLog->channel_label,
                'channel_icon' => $notificationLog->channel_icon,
                'status' => $notificationLog->status,
                'status_label' => $notificationLog->status_label,
                'status_color' => $notificationLog->status_color,
                'template_code' => $notificationLog->template_code,
                'template_label' => $notificationLog->template_label,
                'recipient' => $notificationLog->recipient,
                'subject' => $notificationLog->subject,
                'body' => $notificationLog->body,
                'error_message' => $notificationLog->error_message,
                'customer_name' => $notificationLog->customer?->name,
                'sent_at' => $notificationLog->sent_at?->format('d M Y H:i:s'),
                'created_at' => $notificationLog->created_at->format('d M Y H:i:s'),
            ],
        ]);
    }

    public function destroy(Request $request)
    {
        $user = auth()->user();
        $popId = $user->hasRole('admin') ? $request->get('user_id', $user->id) : $user->id;

        $deleted = 0;

        if ($request->filled('ids')) {
            // Delete selected
            $deleted = NotificationLog::where('pop_id', $popId)
                ->whereIn('id', $request->ids)
                ->delete();
        } elseif ($request->filled('older_than')) {
            // Delete older than N days
            $days = (int) $request->older_than;
            $deleted = NotificationLog::where('pop_id', $popId)
                ->where('created_at', '<', now()->subDays($days))
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => "{$deleted} log notifikasi berhasil dihapus.",
            'deleted' => $deleted,
        ]);
    }

    public function resend(NotificationLog $notificationLog)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && $notificationLog->pop_id !== $user->id) {
            abort(403);
        }

        if (!$notificationLog->customer) {
            return response()->json(['success' => false, 'message' => 'Customer tidak ditemukan.'], 400);
        }

        $notifService = app(\App\Services\NotificationService::class);
        $result = $notifService->sendToCustomer(
            $notificationLog->customer,
            $notificationLog->template_code,
            [],
            [$notificationLog->channel]
        );

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil dikirim ulang.',
            'result' => $result,
        ]);
    }
}
