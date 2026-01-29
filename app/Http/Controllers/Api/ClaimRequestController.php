<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClaimRequest;
use App\Models\Request as HelpRequest;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;

class ClaimRequestController extends Controller
{
    /**
     * CREATE CLAIM (HELP REQUEST)
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'request_id' => 'required|exists:requests,id',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            $req = HelpRequest::findOrFail($request->request_id);

            if ((int) $req->user_id === (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot help your own request.'
                ], 400);
            }

            $existing = ClaimRequest::where('user_id', $user->id)
                ->where('request_id', $req->id)
                ->where('status', 'active')
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already offered to help this request.'
                ], 409);
            }

            $claim = ClaimRequest::create([
                'user_id'    => $user->id,
                'request_id' => $req->id,
                'status'     => 'active',
            ]);

            // ================= NOTIFICATION =================
            NotificationService::requestClaimed($req->user, $req);

            return response()->json([
                'success' => true,
                'message' => 'Help offer submitted successfully.',
                'data'    => $claim
            ], 201);

        } catch (\Exception $e) {
            Log::error('ClaimRequest store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit help offer.'
            ], 500);
        }
    }

    /**
     * VIEW MY CLAIMED REQUESTS
     */
    public function myClaims(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            $claims = ClaimRequest::with(['request', 'request.user'])
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $claims
            ]);

        } catch (\Exception $e) {
            Log::error('ClaimRequest myClaims error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load claims.'
            ], 500);
        }
    }

    /**
     * CANCEL CLAIM
     */
    public function cancelClaim(Request $request)
    {
        try {
            $request->validate([
                'claim_id' => 'required|exists:claim_requests,id'
            ]);

            $user  = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            $claim = ClaimRequest::with('request.user')->findOrFail($request->claim_id);

            if ((int) $claim->user_id !== (int) $user->id || $claim->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized or invalid state.'
                ], 403);
            }

            $claim->update(['status' => 'cancelled']);

            // ================= NOTIFICATION =================
            NotificationService::requestClaimCancelled($claim->request, $user);

            return response()->json([
                'success' => true,
                'message' => 'Claim cancelled successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('ClaimRequest cancel error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel claim.'
            ], 500);
        }
    }

    /**
     * MARK REQUEST AS FULFILLED (HELPER)
     */
    public function markFulfilled(Request $request)
    {
        try {
            $request->validate([
                'claim_id' => 'required|exists:claim_requests,id'
            ]);

            $user  = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            $claim = ClaimRequest::with('request.user')->findOrFail($request->claim_id);

            if ((int) $claim->user_id !== (int) $user->id || $claim->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized or invalid state.'
                ], 403);
            }

            $claim->update(['status' => 'fulfilled']);

            // ================= NOTIFICATION =================
            NotificationService::requestFulfilled($claim->request, $user);

            return response()->json([
                'success' => true,
                'message' => 'Request marked as fulfilled.'
            ]);

        } catch (\Exception $e) {
            Log::error('ClaimRequest fulfill error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark fulfilled.'
            ], 500);
        }
    }
    public function complete(Request $request)
{
    try {
        $request->validate([
            'claim_id' => 'required|exists:claim_requests,id'
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $claim = ClaimRequest::with('request.user')->findOrFail($request->claim_id);

        if ((int) $claim->user_id !== (int) $user->id || $claim->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or invalid state.'
            ], 403);
        }

        $claim->update([
            'status' => 'completed'
        ]);

        NotificationService::requestFulfilled($claim->request, $user);

        return response()->json([
            'success' => true,
            'message' => 'Help marked as completed.'
        ]);

    } catch (\Exception $e) {
        Log::error('ClaimRequest complete error: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Failed to complete help.'
        ], 500);
    }
}

}
