<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request as HttpRequest;
use App\Models\Request as UserRequest;
use App\Services\NotificationService;

class RequestController extends Controller
{
    /**
     * =========================
     * LIST ALL REQUESTS
     * =========================
     */
    public function index()
    {
        $requests = UserRequest::with('user')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.requests.index', compact('requests'));
    }

    /**
     * =========================
     * VIEW REQUEST DETAILS
     * =========================
     */
    public function show($id)
    {
        $request = UserRequest::with([
            'user',
            'images',
            'claimRequests',
        ])->findOrFail($id);

        return view('admin.requests.show', compact('request'));
    }

    /**
     * =========================
     * APPROVE REQUEST
     * =========================
     */
    public function approve($id)
    {
        $request = UserRequest::with('user')->findOrFail($id);

        if ($request->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $request->update([
            'status' => 'approved',
        ]);

        // 🔔 USER NOTIFICATION
        NotificationService::requestApproved($request);

        return back()->with('success', 'Request approved successfully.');
    }

    /**
     * =========================
     * REJECT REQUEST
     * =========================
     */
    public function reject(HttpRequest $httpRequest, $id)
    {
        $request = UserRequest::with('user')->findOrFail($id);

        if ($request->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $request->update([
            'status'        => 'rejected',
            'admin_remark'  => $httpRequest->admin_remark,
        ]);

        // 🔔 USER NOTIFICATION
        NotificationService::requestRejected($request);

        return back()->with('success', 'Request rejected successfully.');
    }

    /**
     * =========================
     * EXPORT REQUESTS (CSV)
     * =========================
     */
    public function export()
    {
        $requests = UserRequest::with('user')
            ->orderByDesc('created_at')
            ->get();

        $filename = 'requests_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($requests) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'User Name',
                'User Email',
                'Item Name',
                'Category',
                'Status',
                'Admin Remark',
                'Address',
                'Submitted At',
            ]);

            foreach ($requests as $request) {
                fputcsv($handle, [
                    $request->id,
                    $request->user->name ?? '-',
                    $request->user->email ?? '-',
                    $request->item_name,
                    $request->category,
                    ucfirst($request->status),
                    $request->admin_remark ?? '',
                    $request->address,
                    $request->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
