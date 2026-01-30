<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClaimOffer;
use App\Models\Offer;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClaimOfferController extends Controller
{
    /**
     * USER CLAIM OFFER
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'offer_id' => 'required|exists:offers,offer_id',
        ]);

        DB::beginTransaction();
        try {
            $offer = Offer::where('offer_id', $request->offer_id)->lockForUpdate()->firstOrFail();

            if ($offer->user_id === $user->id) {
                return response()->json(['success' => false, 'message' => 'You cannot claim your own offer'], 403);
            }

            if ($offer->status !== 'available') {
                return response()->json(['success' => false, 'message' => 'Offer is no longer available'], 409);
            }

            $exists = ClaimOffer::where('offer_id', $offer->offer_id)
                ->where('user_id', $user->id)
                ->whereIn('status', ['active', 'received', 'completed'])
                ->exists();

            if ($exists) {
                return response()->json(['success' => false, 'message' => 'You already claimed this offer'], 409);
            }

            $claim = ClaimOffer::create([
                'offer_id' => $offer->offer_id,
                'user_id'  => $user->id,
                'status'   => 'active',
            ]);

            $offer->update(['status' => 'claimed']);

            DB::commit();

            // 🔔 Notification (SAFE)
            try {
                NotificationService::offerClaimed($offer->user, $user, $offer);
            } catch (\Throwable $e) {
                Log::warning('Offer claim notification failed', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Offer claimed successfully',
                'data'    => $claim,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ClaimOffer store error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to claim offer',
            ], 500);
        }
    }

    /**
     * VIEW MY CLAIMS
     */
    public function myClaims(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $claims = ClaimOffer::with('offer.user')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        return response()->json(['success' => true, 'data' => $claims]);
    }

    /**
     * CANCEL CLAIM (CLAIMER)
     */
    public function cancelClaim(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'claim_id' => 'required|exists:claim_offers,id'
        ]);

        DB::beginTransaction();
        try {
            $claim = ClaimOffer::lockForUpdate()->findOrFail($request->claim_id);

            if ($claim->user_id !== $user->id || $claim->status !== 'active') {
                return response()->json(['success' => false, 'message' => 'Invalid state'], 403);
            }

            $offer = Offer::where('offer_id', $claim->offer_id)->lockForUpdate()->first();

            $claim->update(['status' => 'cancelled']);
            if ($offer) {
                $offer->update(['status' => 'available']);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Claim cancelled successfully']);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Cancel claim error', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to cancel claim'], 500);
        }
    }

    /**
     * MARK RECEIVED (CLAIMER)
     */
    public function markReceived(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'claim_id' => 'required|exists:claim_offers,id'
        ]);

        try {
            $claim = ClaimOffer::findOrFail($request->claim_id);

            if ($claim->user_id !== $user->id || $claim->status !== 'active') {
                return response()->json(['success' => false, 'message' => 'Invalid state'], 403);
            }

            $claim->update(['status' => 'received']);

            return response()->json(['success' => true, 'message' => 'Item marked as received']);

        } catch (\Throwable $e) {
            Log::error('Mark received error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed'], 500);
        }
    }

    /**
     * MARK COLLECTED (OWNER)
     */
    public function markCollected(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'claim_id' => 'required|exists:claim_offers,id'
        ]);

        DB::beginTransaction();
        try {
            $claim = ClaimOffer::lockForUpdate()->findOrFail($request->claim_id);
            $offer = Offer::where('offer_id', $claim->offer_id)->lockForUpdate()->firstOrFail();

            if ($offer->user_id !== $user->id || $claim->status !== 'received') {
                return response()->json(['success' => false, 'message' => 'Invalid state'], 403);
            }

            $claim->update(['status' => 'completed']);
            $offer->update(['status' => 'completed']);

            DB::commit();

            try {
                NotificationService::offerCompleted($offer->user, $offer);
            } catch (\Throwable $e) {
                Log::warning('Offer completed notification failed', ['error' => $e->getMessage()]);
            }

            return response()->json(['success' => true, 'message' => 'Offer completed']);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Mark collected error', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed'], 500);
        }
    }
}
