<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    protected FirebaseAuth $firebaseAuth;

    public function __construct(FirebaseAuth $firebaseAuth)
    {
        $this->firebaseAuth = $firebaseAuth;
    }

    public function firebaseLogin(Request $request)
    {
        try {
            $request->validate([
                'id_token' => 'required|string',
            ]);

            // =========================
            // 1. Verify Firebase token
            // =========================
            $verifiedToken = $this->firebaseAuth
                ->verifyIdToken($request->id_token);

            $claims = $verifiedToken->claims();

            $firebaseUid = $claims->get('sub');
            $email = $claims->get('email');
            $name = $claims->get('name') ?? 'User';

            if (!$email || !$firebaseUid) {
                return response()->json([
                    'message' => 'Invalid Firebase token data'
                ], 401);
            }

            // =========================
            // 2. Find or create SQL user
            // =========================
            $user = User::where('firebase_uid', $firebaseUid)->first();

            $isNewUser = false;

            if (!$user) {
                $user = User::create([
                    'firebase_uid'      => $firebaseUid,
                    'email'             => $email,
                    'name'              => $name,
                    'password'          => bcrypt(Str::random(32)),
                    'email_verified_at' => Carbon::now(),
                ]);

                $isNewUser = true;
            }

            // =========================
            // 3. CREATE / SYNC FIRESTORE
            // =========================
            try {
                $factory = (new Factory)
                    ->withServiceAccount(storage_path('app/firebase-key.json'));

                $firestore = $factory->createFirestore();
                $db = $firestore->database();

                $db->collection('users')
                    ->document($firebaseUid)
                    ->set([
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone_number,
                        'address' => $user->address,
                        'created_at' => $user->created_at?->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ], ['merge' => true]);

            } catch (\Throwable $e) {
                Log::error('Firestore user sync failed', [
                    'uid' => $firebaseUid,
                    'error' => $e->getMessage(),
                ]);
            }

            // =========================
            // 4. Create Sanctum token
            // =========================
            $token = $user->createToken('mobile')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => [
                    'id'    => $user->id,
                    'email' => $user->email,
                    'name'  => $user->name,
                ],
                'is_new_user' => $isNewUser,
            ]);

        } catch (\Throwable $e) {

            Log::error('Firebase login failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Firebase login failed',
                'details' => $e->getMessage(),
            ], 401);
        }
    }
}
