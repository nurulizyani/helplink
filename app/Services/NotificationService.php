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
     | CORE CREATOR (SINGLE SOURCE OF TRUTH)
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
     | SYSTEM / ACCOUNT
     ========================================================= */
    public static function accountCreated(User $user): void
    {
        self::notify(
            $user->id,
            'Welcome to HelpLink',
            "Hi {$user->name}, your account has been successfully created.",
            'system'
        );
    }

    public static function profileUpdated(User $user): void
    {
        self::notify(
            $user->id,
            'Profile Updated',
            "Hi {$user->name}, your profile information has been updated.",
            'system'
        );
    }

    public static function adminUpdatedProfile(User $user): void
    {
        self::notify(
            $user->id,
            'Profile Updated by Admin',
            "Hi {$user->name}, your profile has been updated by the admin.",
            'system'
        );
    }

    /* =========================================================
     | REQUEST MODULE
     ========================================================= */
    public static function requestSubmitted(HelpRequest $request): void
    {
        self::notify(
            $request->user_id,
            'Request Submitted',
            "Your request '{$request->item_name}' has been submitted and is pending review.",
            'request',
            ['request_id' => $request->id]
        );
    }

    public static function requestApproved(HelpRequest $request): void
    {
        self::notify(
            $request->user_id,
            'Request Approved',
            "Your request '{$request->item_name}' has been approved.",
            'request',
            ['request_id' => $request->id]
        );
    }

    public static function requestRejected(HelpRequest $request): void
    {
        self::notify(
            $request->user_id,
            'Request Rejected',
            "Your request '{$request->item_name}' has been rejected.",
            'request',
            ['request_id' => $request->id]
        );
    }

    public static function requestClaimed(User $requestOwner, User $helper, HelpRequest $request): void
    {
        self::notify(
            $requestOwner->id,
            'Someone Offered Help',
            "{$helper->name} has offered to help your request '{$request->item_name}'.",
            'request',
            ['request_id' => $request->id]
        );
    }

    public static function requestFulfilled(User $requestOwner, User $helper, HelpRequest $request): void
    {
        self::notify(
            $requestOwner->id,
            'Request Fulfilled',
            "Your request '{$request->item_name}' has been marked as fulfilled by {$helper->name}.",
            'request',
            ['request_id' => $request->id]
        );
    }

    public static function claimCancelled(User $requestOwner, User $helper, HelpRequest $request): void
    {
        self::notify(
            $requestOwner->id,
            'Help Cancelled',
            "{$helper->name} has cancelled their help for '{$request->item_name}'.",
            'request',
            ['request_id' => $request->id]
        );
    }

    /* =========================================================
     | OFFER MODULE
     ========================================================= */
    public static function offerCreated(User $owner, Offer $offer): void
    {
        self::notify(
            $owner->id,
            'Offer Created',
            "Your offer '{$offer->item_name}' has been created successfully.",
            'offer',
            ['offer_id' => $offer->id]
        );
    }

    public static function offerFlagged(Offer $offer): void
    {
        self::notify(
            $offer->user_id,
            'Offer Flagged',
            "Your offer '{$offer->item_name}' has been flagged for review by admin.",
            'offer',
            ['offer_id' => $offer->id]
        );
    }

    public static function offerClaimed(Offer $offer, User $claimer): void
    {
        self::notify(
            $offer->user_id,
            'Offer Claimed',
            "{$claimer->name} has claimed your offer '{$offer->item_name}'.",
            'offer',
            [
                'offer_id'   => $offer->id,
                'claimer_id' => $claimer->id,
            ]
        );
    }

    public static function offerClaimCancelled(User $owner, Offer $offer): void
    {
        self::notify(
            $owner->id,
            'Claim Cancelled',
            "A claim for your offer '{$offer->item_name}' has been cancelled.",
            'offer',
            ['offer_id' => $offer->id]
        );
    }

    public static function offerReceived(User $owner, Offer $offer): void
    {
        self::notify(
            $owner->id,
            'Item Received',
            "The item '{$offer->item_name}' has been marked as received.",
            'offer',
            ['offer_id' => $offer->id]
        );
    }

    public static function offerCompleted(User $owner, User $claimer, Offer $offer): void
    {
        self::notify(
            $owner->id,
            'Offer Completed',
            "Your offer '{$offer->item_name}' has been successfully completed.",
            'offer',
            ['offer_id' => $offer->id]
        );
    }

    /* =========================================================
 | OFFER MODULE – PUBLIC NOTIFICATION
 ========================================================= */
public static function newOfferAvailable(Offer $offer): void
{
    // notify semua user kecuali owner
    $users = User::where('id', '!=', $offer->user_id)->get();

    foreach ($users as $user) {
        self::notify(
            $user->id,
            'New Offer Available',
            "A new offer '{$offer->item_name}' is now available.",
            'offer',
            [
                'offer_id' => $offer->offer_id,
            ]
        );
    }
}


    /* =========================================================
     | CHAT MODULE
     ========================================================= */
    public static function newChatMessage(Message $message): void
    {
        $conversation = $message->conversation;

        $receiverId = $conversation->user1_id === $message->sender_id
            ? $conversation->user2_id
            : $conversation->user1_id;

        if (!$receiverId) return;

        $sender = User::find($message->sender_id);
        if (!$sender) return;

        self::notify(
            $receiverId,
            'New Message',
            "New message from {$sender->name}",
            'chat',
            [
                'conversation_id' => $conversation->id,
                'sender_id'       => $sender->id,
            ]
        );
    }
}
