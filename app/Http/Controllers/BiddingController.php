<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Bid;
use App\Models\DisposalEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BiddingController extends Controller
{
    public function index()
    {
        $events = DisposalEvent::where('status', 'published')
            ->where('start_date', '<=', Carbon::now())
            ->where('end_date', '>=', Carbon::now())
            ->latest()
            ->get();

        return view('bidder.events.index', compact('events'));
    }

    public function show(DisposalEvent $event)
    {
        $assets = $event->assets()->where('status', 'available')->get();

        // Get bidder's existing bids for this event if logged in and registered
        $myBids = collect();
        if (Auth::check() && Auth::user()->bidder) {
            $myBids = Auth::user()->bidder->bids()
                ->whereHas('asset', function ($query) use ($event) {
                    $query->where('disposal_event_id', $event->id);
                })
                ->get()
                ->keyBy('asset_id');
        }

        return view('bidder.events.show', compact('event', 'assets', 'myBids'));
    }

    public function placeBid(Request $request, Asset $asset)
    {
        $bidder = Auth::user()->bidder;

        // Check if bidder account exists and is approved
        if (!$bidder || !$bidder->isApproved()) {
            return back()->with('error', 'Your account is not approved for bidding.');
        }

        $event = $asset->disposalEvent;

        // Check if event is active
        if (!$event->isActive()) {
            return back()->with('error', 'Bidding is not currently active for this event.');
        }

        // Check if asset is available for bidding
        if ($asset->status !== 'available') {
            return back()->with('error', 'This asset is no longer available for bidding.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $amount = $validated['amount'];

        // Validate based on bid type
        if ($event->bid_type === 'highest_wins') {
            // For highest wins, validate minimum requirements

            // Validate cut-off price (minimum acceptable bid)
            if ($event->cut_off_price && $amount < $event->cut_off_price) {
                return back()->with('error', 'Bid must be at least ₦' . number_format($event->cut_off_price, 2));
            }

            // Validate against current highest bid
            if ($asset->current_highest_bid > 0 && $amount <= $asset->current_highest_bid) {
                return back()->with('error', 'Bid must be higher than current highest bid of ₦' . number_format($asset->current_highest_bid, 2));
            }

            // Validate bid increment
            if ($event->bid_increment) {
                $minBid = $asset->current_highest_bid > 0
                    ? $asset->current_highest_bid + $event->bid_increment
                    : $asset->starting_price + $event->bid_increment;

                if ($amount < $minBid) {
                    return back()->with('error', 'Bid must be at least ₦' . number_format($minBid, 2) . ' (increment: ₦' . number_format($event->bid_increment, 2) . ')');
                }
            } else {
                // If no increment specified, bid must be higher than starting price or current highest
                $minBid = max($asset->starting_price, $asset->current_highest_bid);
                if ($amount <= $minBid) {
                    return back()->with('error', 'Bid must be higher than ₦' . number_format($minBid, 2));
                }
            }
        } else {
            // For lowest wins (reverse auction)

            // Validate cut-off price (maximum acceptable bid)
            if ($event->cut_off_price && $amount > $event->cut_off_price) {
                return back()->with('error', 'Bid must not exceed ₦' . number_format($event->cut_off_price, 2));
            }

            // Validate against current lowest bid
            if ($asset->current_highest_bid > 0 && $amount >= $asset->current_highest_bid) {
                return back()->with('error', 'Bid must be lower than current lowest bid of ₦' . number_format($asset->current_highest_bid, 2));
            }
        }

        try {
            DB::beginTransaction();

            // Create the bid
            Bid::create([
                'asset_id' => $asset->id,
                'bidder_id' => $bidder->id,
                'amount' => $amount,
                'bid_time' => Carbon::now(),
                'status' => 'valid',
            ]);

            // Update asset's current highest/lowest bid
            $asset->update(['current_highest_bid' => $amount]);

            DB::commit();

            Log::info('Bid placed', [
                'bidder_id' => $bidder->id,
                'asset_id' => $asset->id,
                'amount' => $amount,
            ]);

            return back()->with('success', 'Bid placed successfully! Your bid: ₦' . number_format($amount, 2));
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bid placement failed', [
                'bidder_id' => $bidder->id,
                'asset_id' => $asset->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to place bid. Please try again.');
        }
    }

    public function myBids()
    {
        $bidder = Auth::user()->bidder;

        if (!$bidder) {
            return redirect()->route('bidder.register')
                ->with('error', 'Please register as a bidder first.');
        }

        $bids = $bidder->bids()
            ->with('asset.disposalEvent')
            ->latest('bid_time')
            ->paginate(15);

        return view('bidder.bids.index', compact('bids'));
    }

    /**
     * Show bidder's winning bids
     */
    public function myWinnings()
    {
        $bidder = Auth::user()->bidder;

        if (!$bidder) {
            return redirect()->route('bidder.register')
                ->with('error', 'Please register as a bidder first.');
        }

        $winningBids = $bidder->bids()
            ->where('status', 'winner')
            ->with(['asset.disposalEvent'])
            ->latest('bid_time')
            ->get();

        $wonAssets = Asset::where('winner_bidder_id', $bidder->id)
            ->with(['disposalEvent', 'bids' => function ($query) use ($bidder) {
                $query->where('bidder_id', $bidder->id)->where('status', 'winner');
            }])
            ->get();

        return view('bidder.bids.winnings', compact('winningBids', 'wonAssets'));
    }

    /**
     * Get real-time bid information for an asset (without showing bidder identity)
     */
    public function assetBidInfo(Asset $asset)
    {
        $event = $asset->disposalEvent;

        if (!$event->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Event is not active'
            ], 403);
        }

        $bidCount = $asset->bids()->where('status', 'valid')->count();

        return response()->json([
            'success' => true,
            'current_bid' => $asset->current_highest_bid,
            'bid_count' => $bidCount,
            'bid_type' => $event->bid_type,
            'bid_type_label' => $event->bid_type === 'highest_wins' ? 'Highest Bid' : 'Lowest Bid',
            'currency' => '₦',
        ]);
    }
}
