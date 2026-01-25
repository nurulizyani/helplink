<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Offer;
use Illuminate\Support\Facades\Storage;

class OfferController extends Controller
{
    /**
     * =========================
     * LIST ALL OFFERS
     * =========================
     */
    public function index()
    {
        $offers = Offer::with('user')
            ->latest()
            ->get();

        return view('admin.offers.index', compact('offers'));
    }

    /**
     * =========================
     * SHOW OFFER DETAILS
     * =========================
     */
    public function show($id)
    {
        $offer = Offer::with(['user', 'claims.user'])
            ->findOrFail($id);

        return view('admin.offers.show', compact('offer'));
    }

    /**
     * =========================
     * EDIT OFFER (ADMIN)
     * =========================
     */
    public function edit($id)
    {
        $offer = Offer::with('user')->findOrFail($id);
        return view('admin.offers.edit', compact('offer'));
    }

    /**
     * =========================
     * UPDATE OFFER (ADMIN)
     * =========================
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'item_name'   => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity'    => 'required|integer|min:1',
            'status'      => 'required|in:available,claimed,completed,flagged',
        ]);

        $offer = Offer::findOrFail($id);

        $offer->update([
            'item_name'   => $request->item_name,
            'description' => $request->description,
            'quantity'    => $request->quantity,
            'status'      => $request->status,
        ]);

        return redirect()
            ->route('admin.offers.index')
            ->with('success', 'Offer updated successfully.');
    }

    /**
     * =========================
     * FLAG OFFER (ADMIN REVIEW)
     * =========================
     */
    public function flag($id)
    {
        $offer = Offer::findOrFail($id);

        if ($offer->status !== 'available') {
            return back()->with('error', 'Only available offers can be flagged.');
        }

        $offer->update([
            'status' => 'flagged'
        ]);

        return back()->with('success', 'Offer flagged for admin review.');
    }

    /**
     * =========================
     * UNFLAG OFFER
     * =========================
     */
    public function unflag($id)
    {
        $offer = Offer::findOrFail($id);

        if ($offer->status !== 'flagged') {
            return back()->with('error', 'Offer is not flagged.');
        }

        $offer->update([
            'status' => 'available'
        ]);

        return back()->with('success', 'Offer restored successfully.');
    }

    /**
     * =========================
     * DELETE OFFER
     * =========================
     */
    public function destroy($id)
    {
        $offer = Offer::findOrFail($id);

        if ($offer->image) {
            if (Storage::disk('public')->exists($offer->image)) {
                Storage::disk('public')->delete($offer->image);
            }
        }

        $offer->delete();

        return redirect()
            ->route('admin.offers.index')
            ->with('success', 'Offer deleted successfully.');
    }

    /**
     * =========================
     * EXPORT OFFERS CSV
     * =========================
     */
    public function export()
    {
        $offers = Offer::with('user')->latest()->get();

        $filename = 'offers_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($offers) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Offer ID',
                'Item Name',
                'Category',
                'Quantity',
                'Status',
                'Owner Name',
                'Owner Email',
                'Created At',
            ]);

            foreach ($offers as $offer) {
                fputcsv($file, [
                    $offer->offer_id,
                    $offer->item_name,
                    $offer->category,
                    $offer->quantity,
                    ucfirst($offer->status),
                    $offer->user->name ?? '-',
                    $offer->user->email ?? '-',
                    $offer->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
