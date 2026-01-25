<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClaimOffer;
use App\Models\Offer;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class ClaimOfferController extends Controller
{
    /**
     * USER CLAIM OFFER
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            $request->validate([
                'offer_id' => 'required|exists:offers,offer_id',
            ]);

            $offer = Offer::where('offer_id', $request->offer_id)->firstOrFail();

            if ((int) $offer->user_id === (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot claim your own offer.'
                ], 403);
            }

            if ($offer->status !== 'available') {
                return response()->json([
                    'success' => false,
                    'message' => 'This offer is no longer available.'
                ], 409);
            }

            $exists = ClaimOffer::where('offer_id', $offer->offer_id)
                ->where('user_id', $user->id)
                ->whereIn('status', ['active', 'received', 'completed'])
                ->first();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already claimed this offer.'
                ], 409);
            }

            $claim = ClaimOffer::create([
                'offer_id' => $offer->offer_id,
                'user_id'  => $user->id,
                'status'   => 'active',
            ]);

            $offer->update(['status' => 'claimed']);

            $owner = $offer->user;
            NotificationService::offerClaimed($owner, $user, $offer);

            return response()->json([
                'success' => true,
                'message' => 'Offer claimed successfully.',
                'data'    => $claim,
            ], 201);

        } catch (\Exception $e) {
            Log::error('ClaimOffer store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to claim offer.'
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
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $claims = ClaimOffer::with('offer')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $claims
        ]);
    }

    public function cancelClaim(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'claim_id' => 'required|exists:claim_offers,id'
        ]);

        $claim = ClaimOffer::findOrFail($request->claim_id);

        if ((int) $claim->user_id !== (int) $user->id || $claim->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or invalid state.'
            ], 403);
        }

        $offer = Offer::where('offer_id', $claim->offer_id)->first();

        $claim->update(['status' => 'cancelled']);

        if ($offer) {
            $offer->update(['status' => 'available']);

            $owner = $offer->user;
            NotificationService::offerCancelled($owner, $offer);

            $claimer = $claim->user;
            NotificationService::offerCancelledForClaimer($claimer, $offer);
        }

        return response()->json([
            'success' => true,
            'message' => 'Claim cancelled successfully.'
        ]);
    }

    /**
     * MARK RECEIVED (CLAIMER)
     */
    public function markReceived(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            $request->validate([
                'claim_id' => 'required|exists:claim_offers,id'
            ]);

            $claim = ClaimOffer::findOrFail($request->claim_id);

            if ((int) $claim->user_id !== (int) $user->id || $claim->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized or invalid state.'
                ], 403);
            }

            $claim->update(['status' => 'received']);

            $offer = Offer::where('offer_id', $claim->offer_id)->first();
            if ($offer) {
                $owner = $offer->user;
                NotificationService::offerReceived($owner, $offer);

                $claimer = $claim->user;
                NotificationService::offerReceivedForClaimer($claimer, $offer);

            }

            return response()->json([
                'success' => true,
                'message' => 'Item marked as received.'
            ]);

        } catch (\Exception $e) {
            Log::error('ClaimOffer markReceived error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm received.'
            ], 500);
        }
    }

    /**
     * MARK COLLECTED (OWNER)
     */
    public function markCollected(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            $request->validate([
                'claim_id' => 'required|exists:claim_offers,id'
            ]);

            $claim = ClaimOffer::findOrFail($request->claim_id);
            $offer = Offer::where('offer_id', $claim->offer_id)->firstOrFail();

            if ((int) $offer->user_id !== (int) $user->id || $claim->status !== 'received') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized or invalid state.'
                ], 403);
            }

            $claim->update(['status' => 'completed']);
            $offer->update(['status' => 'completed']);

            $owner   = $offer->user;
            $claimer = $claim->user;
            NotificationService::offerCompleted($owner, $claimer, $offer);

            return response()->json([
                'success' => true,
                'message' => 'Offer marked as collected successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('ClaimOffer markCollected error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark collected.'
            ], 500);
        }
    }

    /**
     * GET ACTIVE CLAIM BY OFFER
     */
    public function getByOffer($offerId)
    {
        $user = request()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $claim = ClaimOffer::with('user')
            ->where('offer_id', $offerId)
            ->whereIn('status', ['active', 'received'])
            ->first();

        if (!$claim) {
            return response()->json([
                'success' => false,
                'message' => 'No claim found'
            ]);
        }

        return response()->json([
            'success' => true,
            'claim'   => $claim
        ]);
    }
}
