namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DisposalEvent;
use App\Models\Asset;
use App\Models\Bid;
use App\Notifications\WinnerNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class DisposalEventController extends Controller
{
/**
* Display a listing of disposal events
*/
public function index()
{
$events = DisposalEvent::withCount('assets')
->latest()
->paginate(10);

return view('admin.events.index', compact('events'));
}

/**
* Show the form for creating a new event
*/
public function create()
{
return view('admin.events.create');
}

/**
* Store a newly created event
*/
public function store(Request $request)
{
$validated = $request->validate([
'name' => 'required|string|max:255|unique:disposal_events,name',
'description' => 'nullable|string|max:2000',
'bid_type' => 'required|in:highest_wins,lowest_wins',
'cut_off_price' => 'nullable|numeric|min:0|max:999999999.99',
'bid_increment' => 'nullable|numeric|min:0|max:999999.99',
'start_date' => 'required|date|after_or_equal:now',
'end_date' => 'required|date|after:start_date',
], [
'name.unique' => 'An event with this name already exists.',
'start_date.after_or_equal' => 'Start date must be today or later.',
'end_date.after' => 'End date must be after start date.',
]);

try {
DB::beginTransaction();

$event = DisposalEvent::create([
'name' => $validated['name'],
'description' => $validated['description'] ?? null,
'bid_type' => $validated['bid_type'],
'cut_off_price' => $validated['cut_off_price'] ?? null,
'bid_increment' => $validated['bid_increment'] ?? null,
'start_date' => $validated['start_date'],
'end_date' => $validated['end_date'],
'status' => 'draft',
]);

DB::commit();

Log::info('Disposal event created', [
'event_id' => $event->id,
'name' => $event->name
]);

return redirect()->route('admin.events.index')
->with('success', 'Disposal event "' . $event->name . '" created successfully.');

} catch (\Exception $e) {
DB::rollBack();

Log::error('Event creation failed', [
'error' => $e->getMessage()
]);

return back()
->withInput()
->with('error', 'Failed to create event. Please try again.');
}
}

/**
* Display the specified event
*/
public function show(DisposalEvent $event)
{
$event->load(['assets.winner.user']);
$assets = $event->assets()->withCount('bids')->paginate(15);

$statistics = [
'total_assets' => $event->assets()->count(),
'total_bids' => Bid::whereHas('asset', function($query) use ($event) {
$query->where('disposal_event_id', $event->id);
})->count(),
'assets_with_bids' => $event->assets()->has('bids')->count(),
'total_bid_value' => $event->assets()->sum('current_highest_bid'),
];

return view('admin.events.show', compact('event', 'assets', 'statistics'));
}

/**
* Show the form for editing the specified event
*/
public function edit(DisposalEvent $event)
{
// Prevent editing if event is closed or completed
if (in_array($event->status, ['closed', 'completed'])) {
return back()->with('error', 'Cannot edit a closed or completed event.');
}

return view('admin.events.edit', compact('event'));
}

/**
* Update the specified event
*/
public function update(Request $request, DisposalEvent $event)
{
// Prevent editing if event is closed or completed
if (in_array($event->status, ['closed', 'completed'])) {
return back()->with('error', 'Cannot edit a closed or completed event.');
}

$validated = $request->validate([
'name' => 'required|string|max:255|unique:disposal_events,name,' . $event->id,
'description' => 'nullable|string|max:2000',
'bid_type' => 'required|in:highest_wins,lowest_wins',
'cut_off_price' => 'nullable|numeric|min:0|max:999999999.99',
'bid_increment' => 'nullable|numeric|min:0|max:999999.99',
'start_date' => 'required|date',
'end_date' => 'required|date|after:start_date',
]);

try {
DB::beginTransaction();

$event->update([
'name' => $validated['name'],
'description' => $validated['description'] ?? null,
'bid_type' => $validated['bid_type'],
'cut_off_price' => $validated['cut_off_price'] ?? null,
'bid_increment' => $validated['bid_increment'] ?? null,
'start_date' => $validated['start_date'],
'end_date' => $validated['end_date'],
]);

DB::commit();

Log::info('Event updated', [
'event_id' => $event->id,
'name' => $event->name
]);

return redirect()->route('admin.events.index')
->with('success', 'Event updated successfully.');

} catch (\Exception $e) {
DB::rollBack();

Log::error('Event update failed', [
'event_id' => $event->id,
'error' => $e->getMessage()
]);

return back()
->withInput()
->with('error', 'Failed to update event. Please try again.');
}
}

/**
* Publish the event
*/
public function publish(DisposalEvent $event)
{
if ($event->status !== 'draft') {
return back()->with('error', 'Only draft events can be published.');
}

if ($event->assets()->count() === 0) {
return back()->with('error', 'Cannot publish an event without assets.');
}

try {
$event->update(['status' => 'published']);

Log::info('Event published', [
'event_id' => $event->id,
'name' => $event->name
]);

return back()->with('success', 'Event published successfully!');

} catch (\Exception $e) {
Log::error('Event publication failed', [
'event_id' => $event->id,
'error' => $e->getMessage()
]);

return back()->with('error', 'Failed to publish event.');
}
}

/**
* Close the event and determine winners
*/
public function close(DisposalEvent $event)
{
if ($event->status !== 'published') {
return back()->with('error', 'Only published events can be closed.');
}

try {
DB::beginTransaction();

// Close the event
$event->update([
'status' => 'closed',
'closed_at' => Carbon::now(),
]);

// Determine winners for all assets
$winnersCount = 0;
foreach ($event->assets as $asset) {
$winningBid = null;

if ($event->bid_type === 'highest_wins') {
$winningBid = $asset->bids()
->where('status', 'valid')
->orderBy('amount', 'desc')
->orderBy('bid_time', 'asc') // First bid wins in case of tie
->first();
} else {
$winningBid = $asset->bids()
->where('status', 'valid')
->orderBy('amount', 'asc')
->orderBy('bid_time', 'asc')
->first();
}

if ($winningBid) {
// Mark asset as sold and set winner
$asset->update([
'winner_bidder_id' => $winningBid->bidder_id,
'status' => 'sold',
'payment_status' => 'pending',
]);

// Mark winning bid
$winningBid->update(['status' => 'winner']);

// Mark other bids as invalid
$asset->bids()
->where('id', '!=', $winningBid->id)
->where('status', 'valid')
->update(['status' => 'invalid']);

$winnersCount++;
}
}

DB::commit();

Log::info('Event closed', [
'event_id' => $event->id,
'name' => $event->name,
'winners_determined' => $winnersCount
]);

return back()->with('success', "Event closed successfully! {$winnersCount} winner(s) determined.");

} catch (\Exception $e) {
DB::rollBack();

Log::error('Event closure failed', [
'event_id' => $event->id,
'error' => $e->getMessage()
]);

return back()->with('error', 'Failed to close event. Please try again.');
}
}

/**
* Mark event as completed
*/
public function complete(DisposalEvent $event)
{
if ($event->status !== 'closed') {
return back()->with('error', 'Only closed events can be marked as completed.');
}

try {
$event->update([
'status' => 'completed',
'completed_at' => Carbon::now(),
]);

Log::info('Event completed', [
'event_id' => $event->id,
'name' => $event->name
]);

return back()->with('success', 'Event marked as completed!');

} catch (\Exception $e) {
Log::error('Event completion failed', [
'event_id' => $event->id,
'error' => $e->getMessage()
]);

return back()->with('error', 'Failed to complete event.');
}
}

/**
* Notify all winners for a closed event
*/
public function notifyWinners(DisposalEvent $event)
{
if ($event->status !== 'closed') {
return back()->with('error', 'Can only notify winners for closed events.');
}

if ($event->winners_notified) {
return back()->with('warning', 'Winners have already been notified for this event.');
}

try {
$assets = $event->assets()->whereNotNull('winner_bidder_id')->with('winner.user')->get();

if ($assets->isEmpty()) {
return back()->with('error', 'No winners found for this event.');
}

$notifiedCount = 0;
foreach ($assets as $asset) {
$winningBid = $asset->bids()->where('status', 'winner')->first();

if ($winningBid && $asset->winner && $asset->winner->user) {
$asset->winner->user->notify(new WinnerNotification($asset, $event, $winningBid->amount));
$notifiedCount++;
}
}

$event->update([
'winners_notified' => true,
'winners_notified_at' => Carbon::now(),
]);

Log::info('Winners notified', [
'event_id' => $event->id,
'count' => $notifiedCount,
]);

return back()->with('success', "{$notifiedCount} winner(s) notified successfully!");

} catch (\Exception $e) {
Log::error('Winner notification failed', [
'event_id' => $event->id,
'error' => $e->getMessage()
]);

return back()->with('error', 'Failed to notify winners. Please try again.');
}
}

/**
* Delete the specified event
*/
public function destroy(DisposalEvent $event)
{
// Prevent deletion if event has bids
$hasBids = Bid::whereHas('asset', function($query) use ($event) {
$query->where('disposal_event_id', $event->id);
})->exists();

if ($hasBids) {
return back()->with('error', 'Cannot delete an event that has bids.');
}

try {
DB::beginTransaction();

// Delete all assets and their images
foreach ($event->assets as $asset) {
if ($asset->image && Storage::disk('public')->exists($asset->image)) {
Storage::disk('public')->delete($asset->image);
}
}

$eventName = $event->name;
$event->delete();

DB::commit();

Log::info('Event deleted', [
'event_id' => $event->id,
'name' => $eventName
]);

return redirect()->route('admin.events.index')
->with('success', 'Event deleted successfully.');

} catch (\Exception $e) {
DB::rollBack();

Log::error('Event deletion failed', [
'event_id' => $event->id,
'error' => $e->getMessage()
]);

return back()->with('error', 'Failed to delete event. Please try again.');
}
}

/**
* Generate event report
*/
public function report(DisposalEvent $event)
{
$assets = $event->assets()->with(['winner.user', 'bids'])->get();

$report = [
'event' => $event,
'total_assets' => $assets->count(),
'sold_assets' => $assets->where('status', 'sold')->count(),
'total_revenue' => $assets->sum('current_highest_bid'),
'total_bids' => $assets->sum(function($asset) {
return $asset->bids->count();
}),
'unique_bidders' => $assets->flatMap(function($asset) {
return $asset->bids->pluck('bidder_id');
})->unique()->count(),
];

return view('admin.events.report', compact('event', 'assets', 'report'));
}
}