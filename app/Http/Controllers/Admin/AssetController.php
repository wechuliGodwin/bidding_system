<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\DisposalEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    /**
     * Display a listing of assets for a specific event
     */
    public function index(DisposalEvent $event)
    {
        $assets = $event->assets()->with('winner.user')->paginate(15);
        return view('admin.assets.index', compact('event', 'assets'));
    }

    /**
     * Show the form for creating a new asset
     */
    public function create(DisposalEvent $event)
    {
        return view('admin.assets.create', compact('event'));
    }

    /**
     * Store a newly created asset
     */
    public function store(Request $request, DisposalEvent $event)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048', // 2MB max
            'starting_price' => 'required|numeric|min:0|max:999999999.99',
            'quantity' => 'nullable|integer|min:1',
            'condition' => 'nullable|in:new,excellent,good,fair,poor',
            'location' => 'nullable|string|max:255',
        ], [
            'starting_price.required' => 'Starting price is required.',
            'starting_price.min' => 'Starting price must be at least 0.',
            'image.max' => 'Image size must not exceed 2MB.',
        ]);

        try {
            DB::beginTransaction();

            $assetData = [
                'disposal_event_id' => $event->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'starting_price' => $validated['starting_price'],
                'current_highest_bid' => 0,
                'status' => 'available',
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = 'asset_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('assets', $filename, 'public');
                $assetData['image'] = $path;
            }

            $asset = Asset::create($assetData);

            DB::commit();

            Log::info('Asset created', [
                'asset_id' => $asset->id,
                'event_id' => $event->id,
                'name' => $asset->name
            ]);

            return redirect()->route('admin.events.index')
                ->with('success', 'Asset "' . $asset->name . '" added successfully to event "' . $event->name . '".');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Asset creation failed', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create asset. Please try again.');
        }
    }

    /**
     * Display the specified asset
     */
    public function show(Asset $asset)
    {
        $asset->load(['disposalEvent', 'bids.bidder.user', 'winner.user']);
        $bids = $asset->bids()->with('bidder.user')->orderBy('amount', 'desc')->paginate(10);

        return view('admin.assets.show', compact('asset', 'bids'));
    }

    /**
     * Show the form for editing the specified asset
     */
    public function edit(Asset $asset)
    {
        return view('admin.assets.edit', compact('asset'));
    }

    /**
     * Update the specified asset
     */
    public function update(Request $request, Asset $asset)
    {
        // Prevent editing if asset has bids
        if ($asset->bids()->count() > 0) {
            return back()->with('error', 'Cannot edit asset that already has bids.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'starting_price' => 'required|numeric|min:0|max:999999999.99',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'starting_price' => $validated['starting_price'],
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($asset->image && Storage::disk('public')->exists($asset->image)) {
                    Storage::disk('public')->delete($asset->image);
                }

                $image = $request->file('image');
                $filename = 'asset_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('assets', $filename, 'public');
                $updateData['image'] = $path;
            }

            $asset->update($updateData);

            DB::commit();

            Log::info('Asset updated', [
                'asset_id' => $asset->id,
                'name' => $asset->name
            ]);

            return redirect()->route('admin.events.index')
                ->with('success', 'Asset updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Asset update failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update asset. Please try again.');
        }
    }

    /**
     * Remove the specified asset
     */
    public function destroy(Asset $asset)
    {
        // Prevent deletion if asset has bids
        if ($asset->bids()->count() > 0) {
            return back()->with('error', 'Cannot delete asset that has bids.');
        }

        try {
            DB::beginTransaction();

            // Delete image if exists
            if ($asset->image && Storage::disk('public')->exists($asset->image)) {
                Storage::disk('public')->delete($asset->image);
            }

            $assetName = $asset->name;
            $asset->delete();

            DB::commit();

            Log::info('Asset deleted', [
                'asset_id' => $asset->id,
                'name' => $assetName
            ]);

            return back()->with('success', 'Asset deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Asset deletion failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to delete asset. Please try again.');
        }
    }

    /**
     * Withdraw an asset from bidding
     */
    public function withdraw(Asset $asset)
    {
        if ($asset->status === 'sold') {
            return back()->with('error', 'Cannot withdraw a sold asset.');
        }

        try {
            $asset->update(['status' => 'withdrawn']);

            Log::info('Asset withdrawn', [
                'asset_id' => $asset->id,
                'name' => $asset->name
            ]);

            return back()->with('success', 'Asset withdrawn from bidding.');
        } catch (\Exception $e) {
            Log::error('Asset withdrawal failed', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to withdraw asset.');
        }
    }

    /**
     * Bulk upload assets via CSV
     */
    public function bulkUpload(Request $request, DisposalEvent $event)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            $header = array_shift($csvData); // Remove header row

            $imported = 0;
            foreach ($csvData as $row) {
                if (count($row) >= 3) { // Minimum: name, description, price
                    Asset::create([
                        'disposal_event_id' => $event->id,
                        'name' => $row[0],
                        'description' => $row[1] ?? null,
                        'starting_price' => $row[2],
                        'current_highest_bid' => 0,
                        'status' => 'available',
                    ]);
                    $imported++;
                }
            }

            DB::commit();

            return back()->with('success', "Successfully imported {$imported} assets.");
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk upload failed', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Bulk upload failed. Please check your CSV format.');
        }
    }
}
