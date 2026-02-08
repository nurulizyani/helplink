<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Offer;
use App\Models\Request as HelpRequest;
use App\Models\Message;
use App\Helpers\FCMHelper;

class NotificationService
{
    private static function stringify(array $data): array
    {
        return collect($data)->map(fn ($v) => (string) $v)->toArray();
    }

    /* =========================================================
     | CORE NOTIFICATION (LOW LEVEL)
     ========================================================= */
    private static function notify(
        int $userId,
        string $title,
        ?string $message = null,
        ?string $type = null,
        ?array $data = null
    ): void {
        $notification = Notification::create([
            'user_id' => $userId,
            'title'   => $title,
            'message' => $message,
            'type'    => $type,
            'data'    => $data,
            'is_read' => 0,
        ]);

        $user = User::find($userId);

        // PUSH TO MOBILE (FCM)
        if ($user && $user->fcm_token) {
            $payload = self::stringify(array_merge($data ?? [], [
                'notification_id' => $notification->id,
                'type'            => $type ?? 'system',
            ]));

            FCMHelper::sendPushNotification(
                $user->fcm_token,
                $title,
                $message ?? '',
                $payload
            );
        }
    }

    /* =========================================================
     | SAFE SIMPLE NOTIFICATION (USE THIS EVERYWHERE)
     ========================================================= */
    public static function simple(
        User $user,
        string $title,
        string $message,
        string $type = 'system',
        array $data = []
    ): void {
        self::notify(
            $user->id,
            $title,
            $message,
            $type,
            $data
        );
    }

    /* =========================================================
     | SYSTEM
     ========================================================= */
    public static function accountCreated(User $user): void
    {
        self::simple(
            $user,
            'Welcome to HelpLink',
            "Hi {$user->name}, your account has been successfully created."
        );
    }

    public static function profileUpdated(User $user): void
    {
        self::simple(
            $user,
            'Profile Updated',
            "Hi {$user->name}, your profile information has been updated."
        );
    }

    /* =========================================================
     | REQUEST (SAFE VERSION)
     ========================================================= */
    public static function requestSubmitted(HelpRequest $request): void
    {
        self::simple(
            $request->user,
            'Request Submitted',
            "Your request '{$request->item_name}' has been submitted.",
            'request',
            ['request_id' => $request->id]
        );
    }

    public static function requestCompleted(
        User $owner,
        User $helper,
        HelpRequest $request
    ): void {
        self::simple(
            $owner,
            'Request Completed',
            "{$helper->name} has completed your request '{$request->item_name}'.",
            'request',
            ['request_id' => $request->id]
        );
    }

    /* =========================================================
     | OFFER (SAFE VERSION)
     ========================================================= */
    public static function offerClaimed(User $owner, User $claimer, Offer $offer): void
    {
        self::simple(
            $owner,
            'Offer Claimed',
            "{$claimer->name} has claimed your offer '{$offer->item_name}'.",
            'offer',
            ['offer_id' => $offer->offer_id]
        );
    }

    public static function offerReceived(User $owner, Offer $offer): void
    {
        self::simple(
            $owner,
            'Item Received',
            "The item '{$offer->item_name}' has been marked as received.",
            'offer',
            ['offer_id' => $offer->offer_id]
        );
    }

    public static function offerCompleted(User $owner, Offer $offer): void
    {
        self::simple(
            $owner,
            'Offer Completed',
            "Your offer '{$offer->item_name}' has been completed.",
            'offer',
            ['offer_id' => $offer->offer_id]
        );
    }

    /* =========================================================
     | CHAT
     ========================================================= */
    public static function newChatMessage(Message $message): void
    {
        $conversation = $message->conversation;

        $receiverId = $conversation->user1_id === $message->sender_id
            ? $conversation->user2_id
            : $conversation->user1_id;

        if (!$receiverId) return;

        $receiver = User::find($receiverId);
        if (!$receiver) return;

        self::simple(
            $receiver,
            'New Message',
            $message->message,
            'chat',
            [
                'conversation_id' => $conversation->id,
                'sender_id'       => $message->sender_id,
            ]
        );
    }

    public static function requestClaimCancelled(
        HelpRequest $request,
        User $helper
    ): void {
        self::simple(
            $request->user,
            'Help Cancelled',
            "{$helper->name} cancelled help for '{$request->item_name}'.",
            'request',
            ['request_id' => $request->id]
        );
    }

    /* =========================================================
 | REQUEST (ADMIN ACTION)
 ========================================================= */
public static function requestApproved(HelpRequest $request): void
{
    self::simple(
        $request->user,
        'Request Approved',
        "Your request '{$request->item_name}' has been approved by the admin.",
        'request',
        ['request_id' => $request->id]
    );
}

public static function requestRejected(HelpRequest $request): void
{
    self::simple(
        $request->user,
        'Request Rejected',
        "Your request '{$request->item_name}' was rejected. Reason: " .
        ($request->admin_remark ?? 'Not specified.'),
        'request',
        ['request_id' => $request->id]
    );
}

}
