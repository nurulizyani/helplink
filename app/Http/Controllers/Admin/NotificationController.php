<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * =========================
     * LIST ALL NOTIFICATIONS (ADMIN VIEW)
     * =========================
     */
    public function index()
    {
        // Admin can view all notifications (system log)
        $notifications = Notification::orderByDesc('created_at')->get();

        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * =========================
     * MARK SINGLE NOTIFICATION AS READ
     * =========================
     */
    public function markAsRead($id)
    {
        Notification::where('id', $id)->update([
            'is_read' => 1,
        ]);

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * =========================
     * MARK ALL NOTIFICATIONS AS READ
     * =========================
     */
    public function readAll()
    {
        Notification::where('is_read', 0)->update([
            'is_read' => 1,
        ]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * =========================
     * GET UNREAD NOTIFICATIONS (AJAX – TOPBAR)
     * =========================
     */
    public function unread()
    {
        $unread = Notification::where('is_read', 0)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'count' => Notification::where('is_read', 0)->count(),
            'notifications' => $unread->map(function ($n) {
                return [
                    'id'      => $n->id,
                    'title'   => $n->title ?? 'Notification',
                    'message' => $n->message,
                    'type'    => $n->type,
                    'time'    => $n->created_at->diffForHumans(),
                ];
            }),
        ]);
    }
}
