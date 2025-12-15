@extends('layouts.app')
@section('title', 'My Bids')
@section('content')
<h2 class="mb-4">My Bidding History</h2>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Asset</th>
                    <th>Event</th>
                    <th>Bid Amount</th>
                    <th>Bid Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bids as $bid)
                <tr>
                    <td>{{ $bid->asset->name }}</td>
                    <td>{{ $bid->asset->disposalEvent->name }}</td>
                    <td>${{ number_format($bid->amount, 2) }}</td>
                    <td>{{ $bid->bid_time->format('M d, Y H:i:s') }}</td>
                    <td>
                        @if($bid->status === 'winner')
                            <span class="badge bg-success"><i class="fas fa-trophy"></i> Winner</span>
                        @elseif($bid->amount == $bid->asset->current_highest_bid)
                            <span class="badge bg-primary">Highest Bid</span>
                        @else
                            <span class="badge bg-secondary">Outbid</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">You haven't placed any bids yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $bids->links() }}
    </div>
</div>
@endsection