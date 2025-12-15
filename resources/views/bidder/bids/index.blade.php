@extends('layouts.app')
@section('title', 'My Bids')
@section('content')
<div class="container py-4">
    <h2 class="mb-4"><i class="fas fa-list"></i> My Bids</h2>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Note:</strong> Bids cannot be deleted once placed. However, if you've been outbid, you can place a higher bid on the same item by visiting the event page.
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            @if($bids->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Event</th>
                            <th>Asset</th>
                            <th>Bid Amount</th>
                            <th>Bid Time</th>
                            <th>Status</th>
                            <th>Current Highest</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bids as $bid)
                        <tr>
                            <td>{{ $bid->asset->disposalEvent->name }}</td>
                            <td>{{ $bid->asset->name }}</td>
                            <td class="fw-bold">${{ number_format($bid->amount, 2) }}</td>
                            <td>{{ $bid->bid_time->format('M d, Y H:i') }}</td>
                            <td>
                                @if($bid->status === 'winner')
                                <span class="badge bg-success">
                                    <i class="fas fa-trophy"></i> Winner
                                </span>
                                @elseif($bid->status === 'valid')
                                @if($bid->asset->current_highest_bid == $bid->amount)
                                <span class="badge bg-primary">
                                    <i class="fas fa-crown"></i> Leading
                                </span>
                                @else
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-clock"></i> Outbid
                                </span>
                                @endif
                                @elseif($bid->status === 'nullified')
                                <span class="badge bg-danger">
                                    <i class="fas fa-times"></i> Nullified
                                </span>
                                @else
                                <span class="badge bg-secondary">{{ ucfirst($bid->status) }}</span>
                                @endif
                            </td>
                            <td class="text-success fw-bold">${{ number_format($bid->asset->current_highest_bid, 2) }}</td>
                            <td>
                                @if($bid->status === 'valid' && $bid->asset->current_highest_bid > $bid->amount && $bid->asset->disposalEvent->isActive())
                                <a href="{{ route('bidder.events.show', $bid->asset->disposalEvent) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-redo"></i> Rebid
                                </a>
                                @elseif($bid->asset->disposalEvent->isActive())
                                <a href="{{ route('bidder.events.show', $bid->asset->disposalEvent) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                @else
                                <span class="text-muted">Closed</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $bids->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <p class="lead">You haven't placed any bids yet.</p>
                <a href="{{ route('bidder.events.index') }}" class="btn btn-primary">
                    <i class="fas fa-gavel"></i> Browse Events
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection