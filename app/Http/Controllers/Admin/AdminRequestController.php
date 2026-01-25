<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request as HttpRequest;
use App\Models\Request as HelpRequest;
use App\Services\NotificationService;
use App\Models\Notification;
use App\Services\AiRequestAnalyzer;
use Illuminate\Support\Facades\Storage;

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

        //FILTER BY STATUS
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

        // =========================
        // AI ANALYSIS TRIGGER
        // =========================
        if (
            $request->supporting_document &&
            !$request->ai_summary
        ) {
            $path = storage_path('app/' . $request->supporting_document);

            $result = AiRequestAnalyzer::analyzeDocument($path, [
                'category' => $request->category,
                'item'     => $request->item_name,
            ]);

            if (!empty($result)) {
                $request->update([
                    'document_type' => $result['document_type'] ?? 'Unknown',
                    'document_date' => $result['document_date'] ?? null,
                    'ai_summary'    => $result['summary'] ?? null,
                    'ai_confidence' => $result['confidence'] ?? 0,
                    'ai_metadata'   => $result['extracted_data'] ?? null,
                ]);
            }
        }

        $request->refresh();

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
        'status' => 'required|in:approved,rejected,fulfilled',

        // admin_remark WAJIB kalau rejected
        'admin_remark' => [
            'nullable',
            'string',
            'max:1000',
            function ($attribute, $value, $fail) use ($request) {
                if ($request->status === 'rejected' && empty($value)) {
                    $fail('Admin remark is required when rejecting a request.');
                }
            },
        ],
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
