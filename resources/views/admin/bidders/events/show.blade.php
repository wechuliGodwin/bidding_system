@extends('layouts.app')
@section('title', $event->name)
@section('content')
<div class="mb-4">
    <a href="{{ route('bidder.events.index') }}" class="btn btn-secondary">&larr; Back to Events</a>
</div>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0">{{ $event->name }}</h3>
    </div>
    <div class="card-body">
        <p>{{ $event->description }}</p>
        <div class="row">
            <div class="col-md-4">
                <strong>Start:</strong> {{ $event->start_date->format('M d, Y H:i') }}
            </div>
            <div class="col-md-4">
                <strong>End:</strong> {{ $event->end_date->format('M d, Y H:i') }}
            </div>
            <div class="col-md-4">
                <strong>Minimum Bid:</strong> ${{ number_format($event->cut_off_price, 2) }}
            </div>
        </div>
    </div>
</div>

<h4>Available Assets</h4>
<div class="row">
    @forelse($assets as $asset)
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            @if($asset->image)
                <img src="{{ asset('storage/' . $asset->image) }}" class="card-img-top" alt="{{ $asset->name }}" style="height: 200px; object-fit: cover;">
            @else
                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                    <i class="fas fa-image fa-3x text-white"></i>
                </div>
            @endif
            <div class="card-body">
                <h5 class="card-title">{{ $asset->name }}</h5>
                <p class="card-text">{{ Str::limit($asset->description, 100) }}</p>
                <dl class="row">
                    <dt class="col-sm-6">Starting Price:</dt>
                    <dd class="col-sm-6">${{ number_format($asset->starting_price, 2) }}</dd>

                    <dt class="col-sm-6">Current Bid:</dt>
                    <dd class="col-sm-6 text-success fw-bold">${{ number_format($asset->current_highest_bid, 2) }}</dd>
                </dl>
            </div>
            <div class="card-footer">
                @if(auth()->user()->bidder && auth()->user()->bidder->isApproved())
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#bidModal{{ $asset->id }}">
                        Place Bid
                    </button>
                @else
                    <button class="btn btn-secondary w-100" disabled>Approval Required</button>
                @endif
            </div>
        </div>
    </div>

    <!-- Bid Modal -->
    <div class="modal fade" id="bidModal{{ $asset->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Place Bid - {{ $asset->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('bidder.assets.bid', $asset) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Current Highest Bid</label>
                            <input type="text" class="form-control" value="${{ number_format($asset->current_highest_bid, 2) }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Your Bid Amount</label>
                            <input type="number" name="amount" class="form-control" step="0.01" 
                                   min="{{ $asset->current_highest_bid + 0.01 }}" required>
                            <small class="form-text text-muted">
                                Minimum bid: ${{ number_format(max($event->cut_off_price ?? 0, $asset->current_highest_bid + ($event->bid_increment ?? 0.01)), 2) }}
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Bid</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-md-12">
        <div class="alert alert-info">No assets available for bidding in this event.</div>
    </div>
    @endforelse
</div>
@endsection