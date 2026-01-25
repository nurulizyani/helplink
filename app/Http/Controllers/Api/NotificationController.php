<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * =========================
     * GET MY NOTIFICATIONS
     * =========================
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * =========================
     * MARK AS READ
     * =========================
     */
    public function markAsRead($id, Request $request)
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notification->update([
            'is_read' => 1,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function unreadCount(Request $request)
{
    $user = $request->user();
    if (!$user) {
        return response()->json(['count' => 0]);
    }

    $count = \App\Models\Notification::where('user_id', $user->id)
        ->where('is_read', 0)
        ->count();

    return response()->json([
        'success' => true,
        'count'   => $count
    ]);
}

}
