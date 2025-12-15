<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AssetManagementController extends Controller
{
    /**
     * Show all assets with winners for an event
     */
    public function winners($eventId)
    {
        $assets = Asset::where('disposal_event_id', $eventId)
            ->whereNotNull('winner_bidder_id')
            ->with(['winner.user', 'disposalEvent'])
            ->paginate(15);

        return view('admin.assets.winners', compact('assets', 'eventId'));
    }

    /**
     * Nullify a bid (admin override)
     */
    public function nullifyBid(Request $request, Bid $bid)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $asset = $bid->asset;
            $event = $asset->disposalEvent;

            // Can only nullify if event is closed but not completed
            if ($event->status !== 'closed') {
                return back()->with('error', 'Can only nullify bids for closed events.');
            }

            // Nullify the bid
            $bid->update([
                'status' => 'nullified',
                'nullification_reason' => $validated['reason'],
                'nullified_by' => auth()->id(),
                'nullified_at' => Carbon::now(),
            ]);

            // If this was the winning bid, need to recalculate winner
            if ($bid->bidder_id === $asset->winner_bidder_id) {
                // Find next valid bid
                $nextWinningBid = null;

                if ($event->bid_type === 'highest_wins') {
                    $nextWinningBid = $asset->bids()
                        ->where('status', 'valid')
                        ->orderBy('amount', 'desc')
                        ->orderBy('bid_time', 'asc')
                        ->first();
                } else {
                    $nextWinningBid = $asset->bids()
                        ->where('status', 'valid')
                        ->orderBy('amount', 'asc')
                        ->orderBy('bid_time', 'asc')
                        ->first();
                }

                if ($nextWinningBid) {
                    $nextWinningBid->update(['status' => 'winner']);
                    $asset->update([
                        'winner_bidder_id' => $nextWinningBid->bidder_id,
                        'current_highest_bid' => $nextWinningBid->amount,
                    ]);
                } else {
                    // No other valid bids
                    $asset->update([
                        'winner_bidder_id' => null,
                        'status' => 'available',
                        'payment_status' => null,
                    ]);
                }
            }

            DB::commit();

            Log::info('Bid nullified', [
                'bid_id' => $bid->id,
                'asset_id' => $asset->id,
                'reason' => $validated['reason'],
                'nullified_by' => auth()->id(),
            ]);

            return back()->with('success', 'Bid nullified successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bid nullification failed', [
                'bid_id' => $bid->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to nullify bid. Please try again.');
        }
    }

    /**
     * Update payment status
     */
    public function updatePayment(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:pending,partial,completed,failed',
            'payment_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $updateData = [
                'payment_status' => $validated['payment_status'],
            ];

            if (isset($validated['payment_amount'])) {
                $updateData['payment_amount'] = $validated['payment_amount'];
            }

            if ($validated['payment_status'] === 'completed') {
                $updateData['payment_completed_at'] = Carbon::now();
                $updateData['handover_status'] = 'pending';
            }

            $asset->update($updateData);

            Log::info('Payment status updated', [
                'asset_id' => $asset->id,
                'payment_status' => $validated['payment_status'],
            ]);

            return back()->with('success', 'Payment status updated successfully.');
        } catch (\Exception $e) {
            Log::error('Payment update failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to update payment status.');
        }
    }

    /**
     * Record asset handover
     */
    public function recordHandover(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'handover_date' => 'required|date',
            'handover_notes' => 'nullable|string|max:1000',
        ]);

        try {
            // Ensure payment is completed before handover
            if ($asset->payment_status !== 'completed') {
                return back()->with('error', 'Payment must be completed before recording handover.');
            }

            $asset->update([
                'handover_status' => 'completed',
                'handover_date' => $validated['handover_date'],
                'handover_notes' => $validated['handover_notes'] ?? null,
            ]);

            Log::info('Asset handover recorded', [
                'asset_id' => $asset->id,
                'handover_date' => $validated['handover_date'],
            ]);

            return back()->with('success', 'Asset handover recorded successfully.');
        } catch (\Exception $e) {
            Log::error('Handover recording failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to record handover.');
        }
    }
}
