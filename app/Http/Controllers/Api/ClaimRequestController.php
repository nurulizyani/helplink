<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClaimRequest;
use App\Models\Request as HelpRequest;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class ClaimRequestController extends Controller
{

    public function store(Request $request)
{
    DB::beginTransaction();

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

        // ❌ Tak boleh bantu request sendiri
        if ((int) $req->user_id === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot help your own request.'
            ], 403);
        }

        // ❌ Tak boleh bantu kalau request dah tutup
        if ($req->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'This request is no longer available.'
            ], 409);
        }

        // ❌ Elak duplicate claim
        $existing = ClaimRequest::where('user_id', $user->id)
            ->where('request_id', $req->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You are already helping this request.'
            ], 409);
        }

        $claim = ClaimRequest::create([
            'user_id'    => $user->id,
            'request_id' => $req->id,
            'status'     => 'active',
        ]);

        DB::commit();

        // 🔔 Notification (tak ganggu API)
        try {
            NotificationService::requestClaimed(
                $req->user,
                $user,
                $req
            );
        } catch (\Throwable $e) {
            Log::warning('Request claimed notification failed', [
                'claim_id' => $claim->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Help request submitted successfully.',
            'data'    => $claim
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('ClaimRequest store error: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Failed to submit help request.'
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

            $query = ClaimRequest::with(['request', 'request.user'])
                ->where('user_id', $user->id);

            // FILTER STATUS (jika ada)
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $claims = $query
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

    public function complete(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'claim_id' => 'required|exists:claim_requests,id',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            $claim = ClaimRequest::with('request.user')
                ->findOrFail($request->claim_id);

            // ❌ Pastikan helper sahaja boleh complete
            if ((int) $claim->user_id !== (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.'
                ], 403);
            }

            if ($claim->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid claim state.'
                ], 409);
            }

            // ✅ UPDATE STATUS
            $claim->update([
                'status' => 'completed',
            ]);

            $claim->request->update([
                'status' => 'fulfilled',
            ]);

            DB::commit();

            // 🔔 Notification (SAFE)
            try {
                NotificationService::requestCompleted(
                    $claim->request->user, // owner
                    $user,                 // helper
                    $claim->request
                );
            } catch (\Throwable $e) {
                Log::warning('Request completed notification failed', [
                    'claim_id' => $claim->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Help marked as completed.'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ClaimRequest complete error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete help.'
            ], 500);
        }
    }
}
