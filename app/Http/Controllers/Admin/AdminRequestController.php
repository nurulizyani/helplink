<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request as HttpRequest;
use App\Models\Request as HelpRequest;
use App\Services\NotificationService;
use App\Models\Notification;


class AdminRequestController extends Controller
{
    /**
     * =========================
     * LIST REQUESTS (WITH FILTER)
     * =========================
     */
    public function index(HttpRequest $request)
    {
        $query = HelpRequest::with('user')
            ->orderByDesc('created_at');

        // 🔍 FILTER BY STATUS
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $requests = $query->get();

        return view('admin.requests.index', compact('requests'));
    }

    /**
     * =========================
     * VIEW REQUEST DETAILS
     * =========================
     */
    public function show($id)
    {
        $request = HelpRequest::with([
            'user',
            'claimRequests',
        ])->findOrFail($id);

        return view('admin.requests.show', compact('request'));
    }

    /**
     * =========================
     * UPDATE STATUS (APPROVE / REJECT / FULFILLED)
     * =========================
     */
    public function updateStatus(HttpRequest $request, $id)
{
    $req = HelpRequest::with('user')->findOrFail($id);

    $request->validate([
        'status'       => 'required|in:approved,rejected,fulfilled',
        'admin_remark' => 'nullable|string|max:1000',
    ]);

    $status = strtolower($request->status);

    $req->update([
        'status'       => $status,
        'admin_remark' => $request->admin_remark,
    ]);

    // ================= NOTIFICATION =================
    if ($status === 'approved') {
        NotificationService::requestApproved($req);
    }

    if ($status === 'rejected') {
        NotificationService::requestRejected($req);
    }

    return redirect()
        ->route('admin.requests.index')
        ->with('success', 'Request status updated successfully.');
}


    /**
     * =========================
     * EXPORT REQUESTS (CSV)
     * =========================
     */
    public function export()
    {
        $fileName = 'requests_' . now()->format('Ymd_His') . '.csv';

        $requests = HelpRequest::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = [
            "Content-Type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
        ];

        $callback = function () use ($requests) {
            $handle = fopen('php://output', 'w');

            // CSV HEADER
            fputcsv($handle, [
                'ID',
                'User Name',
                'User Email',
                'Item',
                'Category',
                'Status',
                'Admin Remark',
                'Created At',
            ]);

            foreach ($requests as $req) {
                fputcsv($handle, [
                    $req->id,
                    $req->user->name ?? '-',
                    $req->user->email ?? '-',
                    $req->item_name,
                    $req->category,
                    $req->status,
                    $req->admin_remark ?? '',
                    $req->created_at,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
