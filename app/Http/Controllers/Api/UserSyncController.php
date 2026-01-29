<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

class UserSyncController extends Controller
{
    /**
     * Sync user from mobile (after Firebase login)
     */
    public function syncUser(Request $request)
{
    try {
        $request->validate([
            'firebase_uid'   => 'required|string',
            'name'           => 'nullable|string',
            'email'          => 'required|email',
            'phone'          => 'nullable|string',
            'address'        => 'nullable|string',
            'email_verified' => 'nullable|boolean',
        ]);

        Log::info('[SYNC USER PAYLOAD]', $request->only([
            'firebase_uid',
            'email',
            'email_verified'
        ]));

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // CREATE ONLY ONCE
            $user = User::create([
                'firebase_uid' => $request->firebase_uid,
                'name'         => $request->name ?? 'User',
                'email'        => $request->email,
                'phone_number' => $request->phone,
                'address'      => $request->address,
                'password'     => bcrypt('firebase_user'),
                'email_verified_at' => $request->email_verified ? now() : null,
            ]);
        } else {
            // UPDATE ONLY IF DATA CHANGES
            $dirty = false;

            if ($user->firebase_uid !== $request->firebase_uid) {
                $user->firebase_uid = $request->firebase_uid;
                $dirty = true;
            }

            if ($request->name && $user->name !== $request->name) {
                $user->name = $request->name;
                $dirty = true;
            }

            if ($request->phone && $user->phone_number !== $request->phone) {
                $user->phone_number = $request->phone;
                $dirty = true;
            }

            if ($request->address && $user->address !== $request->address) {
                $user->address = $request->address;
                $dirty = true;
            }

            if ($request->email_verified === true && !$user->email_verified_at) {
                $user->email_verified_at = now();
                $dirty = true;
            }

            if ($dirty) {
                $user->save(); // SAVE ONCE ONLY
            }
        }

        return response()->json([
            'success'        => true,
            'data'           => $user,
            'email_verified' => (bool) $user->email_verified_at,
        ]);

    } catch (\Exception $e) {
        Log::error('[SYNC USER ERROR]', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to sync user',
        ], 500);
    }
}


    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
{
    $user = $request->user();

    return response()->json([
        'success' => true,
        'data' => [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'address'      => $user->address,
            'phone_number' => $user->phone_number,
            'profile_image' => $user->profile_image,
        ],
    ]);
}


    /**
     * Update profile (mobile)
     */
    public function updateProfile(Request $request)
{
    try {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $validated = $request->validate([
            'phone'     => 'nullable|string|max:20',
            'address'   => 'nullable|string|max:255',
            'profile_image' => 'nullable|url',
        ]);

        if (isset($validated['phone'])) {
            $user->phone_number = $validated['phone'];
        }

        if (isset($validated['address'])) {
            $user->address = $validated['address'];
        }

        if (isset($validated['profile_image'])) {
            $user->profile_image = $validated['profile_image'];
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);

    } catch (\Exception $e) {
        Log::error('[UPDATE PROFILE ERROR]', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to update profile',
        ], 500);
    }
}

    /**
     * Delete user account
     */
    public function deleteProfile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            DB::beginTransaction();

            DB::table('conversations')
                ->where('user1_id', $user->id)
                ->orWhere('user2_id', $user->id)
                ->delete();

            DB::table('messages')
                ->where('sender_id', $user->id)
                ->delete();

            DB::table('offers')->where('user_id', $user->id)->delete();
            DB::table('requests')->where('user_id', $user->id)->delete();
            DB::table('claim_offers')->where('user_id', $user->id)->delete();
            DB::table('claim_requests')->where('user_id', $user->id)->delete();

            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Account deleted permanently',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[DELETE PROFILE] ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account',
            ], 500);
        }
    }

    /**
     * Save FCM token
     */
    public function saveFcmToken(Request $request)
{
    try {
        // Ambil user daripada Sanctum token
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'success' => true,
        ]);

    } catch (\Exception $e) {
        Log::error('[FCM SAVE TOKEN ERROR]', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to save FCM token',
        ], 500);
    }
}

}
