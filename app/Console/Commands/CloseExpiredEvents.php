<?php

namespace App\Console\Commands;

use App\Models\DisposalEvent;
use App\Models\Asset;
use App\Models\Bid;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CloseExpiredEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:close-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically close expired disposal events and select winners';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired events...');

        // Find all published events that have ended
        $expiredEvents = DisposalEvent::where('status', 'published')
            ->where('end_date', '<', Carbon::now())
            ->get();

        if ($expiredEvents->isEmpty()) {
            $this->info('No expired events found.');
            return 0;
        }

        $this->info("Found {$expiredEvents->count()} expired event(s).");

        foreach ($expiredEvents as $event) {
            try {
                DB::beginTransaction();

                $this->info("Processing event: {$event->name}");

                // Close the event
                $event->update([
                    'status' => 'closed',
                    'closed_at' => Carbon::now(),
                ]);

                // Select winners for each asset
                $this->selectWinners($event);

                DB::commit();

                $this->info("✓ Event '{$event->name}' closed successfully.");

                Log::info('Event auto-closed', [
                    'event_id' => $event->id,
                    'event_name' => $event->name,
                    'closed_at' => Carbon::now(),
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                $this->error("✗ Failed to close event '{$event->name}': {$e->getMessage()}");

                Log::error('Event auto-close failed', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Event closure process completed.');
        return 0;
    }

    /**
     * Select winners for all assets in the event
     */
    private function selectWinners(DisposalEvent $event)
    {
        $assets = $event->assets()->where('status', 'available')->get();

        foreach ($assets as $asset) {
            $winningBid = null;

            if ($event->bid_type === 'highest_wins') {
                // Get highest valid bid
                $winningBid = $asset->bids()
                    ->where('status', 'valid')
                    ->orderBy('amount', 'desc')
                    ->orderBy('bid_time', 'asc') // First bid wins in case of tie
                    ->first();
            } else {
                // Get lowest valid bid
                $winningBid = $asset->bids()
                    ->where('status', 'valid')
                    ->orderBy('amount', 'asc')
                    ->orderBy('bid_time', 'asc') // First bid wins in case of tie
                    ->first();
            }

            if ($winningBid) {
                // Mark the winning bid
                $winningBid->update(['status' => 'winner']);

                // Update asset with winner
                $asset->update([
                    'winner_bidder_id' => $winningBid->bidder_id,
                    'status' => 'sold',
                    'payment_status' => 'pending',
                ]);

                $this->info("  ✓ Winner selected for asset '{$asset->name}'");
            } else {
                $this->info("  - No valid bids for asset '{$asset->name}'");
            }
        }
    }
}
