<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Bell-dropdown notification endpoints (database channel).
 *
 * Auth is via standard `auth` middleware on the parent admin route group;
 * each user only ever sees their OWN notifications via the
 * `auth()->user()->notifications` relation provided by the Notifiable trait.
 */
class UserNotificationController extends Controller
{
    /**
     * Polling endpoint hit by the bell dropdown (every poll_interval seconds).
     *
     * Response: {
     *   unread_count: int,
     *   total_count: int,
     *   latest: [ {id, type, data, read_at, created_at, age}, ... up to 10 ]
     * }
     */
    public function poll(Request $request)
    {
        $user = $request->user();

        $latest = $user->notifications()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => class_basename($n->type),
                'data'       => $n->data,
                'read_at'    => optional($n->read_at)?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
                'age'        => $n->created_at->diffForHumans(),
            ]);

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'total_count'  => $user->notifications()->count(),
            'latest'       => $latest,
        ]);
    }

    /** Mark one notification as read. */
    public function markRead(Request $request, string $id)
    {
        $n = $request->user()->notifications()->where('id', $id)->firstOrFail();
        if (!$n->read_at) {
            $n->markAsRead();
        }
        return response()->json(['success' => true]);
    }

    /** Mark every unread notification of the current user as read. */
    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    /** Delete a single notification. */
    public function destroy(Request $request, string $id)
    {
        $n = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $n->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Full list page (paginated) — opened from "Lihat semua" link in the dropdown.
     */
    public function index(Request $request)
    {
        $items = $request->user()->notifications()->paginate(25);
        return view('admin.notifications.index', compact('items'));
    }
}
