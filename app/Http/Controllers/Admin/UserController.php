<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Services\FirestoreService;
use App\Services\NotificationService;

class UserController extends Controller
{
    /**
     * ===============================
     * LIST ALL USERS
     * ===============================
     */
    public function index()
    {
        $users = User::orderByDesc('created_at')->get();
        return view('admin.users.index', compact('users'));
    }

    /**
     * ===============================
     * SHOW USER
     * ===============================
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.show', compact('user'));
    }

    /**
     * ===============================
     * EDIT USER
     * ===============================
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    /**
     * ===============================
     * UPDATE USER (SQL + FIRESTORE)
     * ===============================
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // ---------- VALIDATION ----------
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $id,
            'phone_number'  => 'nullable|string|max:20',
            'address'       => 'nullable|string|max:255',
        ]);

        Log::info('ADMIN USER UPDATE', [
            'user_id'      => $user->id,
            'firebase_uid' => $user->firebase_uid,
        ]);

        // ===============================
        // 1. UPDATE SQL (MySQL)
        // ===============================
        $user->update([
            'name'         => $request->name,
            'email'        => $request->email,
            'phone_number' => $request->phone_number,
            'address'      => $request->address,
        ]);

        // ===============================
        // 2. SYNC TO FIRESTORE (REST)
        // ===============================
        if ($user->firebase_uid) {
            try {
                app(FirestoreService::class)->updateUser(
                    $user->firebase_uid,
                    [
                        'name'       => $user->name,
                        'email'      => $user->email,
                        'phone'      => $user->phone_number,
                        'address'    => $user->address,
                        'updated_at' => now(),
                    ]
                );

                Log::info('FIRESTORE SYNC SUCCESS', [
                    'firebase_uid' => $user->firebase_uid,
                ]);

            } catch (\Throwable $e) {
                Log::error('FIRESTORE SYNC FAILED', [
                    'firebase_uid' => $user->firebase_uid,
                    'error'        => $e->getMessage(),
                ]);
            }
        }

        // ===============================
        // 3. NOTIFY USER
        // ===============================
        NotificationService::adminUpdatedProfile($user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * ===============================
     * DELETE USER
     * ===============================
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        Log::warning('ADMIN DELETE USER', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * ===============================
     * EXPORT USERS (CSV)
     * ===============================
     */
    public function export()
    {
        $users = User::latest()->get();

        $filename = 'users_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'User ID',
                'Name',
                'Email',
                'Email Status',
                'Joined Date',
            ]);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->email_verified_at ? 'Verified' : 'Unverified',
                    optional($user->created_at)->format('d M Y'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
