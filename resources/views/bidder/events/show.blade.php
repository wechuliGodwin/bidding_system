@extends('layouts.app')
@section('title', $event->name)
@section('content')
<div class="container py-4">
    <div class="mb-4">
        <a href="{{ route('bidder.events.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Events
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0"><i class="fas fa-gavel"></i> {{ $event->name }}</h3>
        </div>
        <div class="card-body">
            <p class="lead">{{ $event->description }}</p>
            <div class="row">
                <div class="col-md-3">
                    <strong><i class="fas fa-calendar-start"></i> Start:</strong><br>
                    {{ $event->start_date->format('M d, Y H:i') }}
                </div>
                <div class="col-md-3">
                    <strong><i class="fas fa-calendar-times"></i> End:</strong><br>
                    {{ $event->end_date->format('M d, Y H:i') }}
                </div>
                <div class="col-md-3">
                    <strong><i class="fas fa-dollar-sign"></i> Minimum Bid:</strong><br>
                    ${{ number_format($event->cut_off_price ?? 0, 2) }}
                </div>
                <div class="col-md-3">
                    <strong><i class="fas fa-trophy"></i> Bid Type:</strong><br>
                    <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $event->bid_type)) }}</span>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mb-3"><i class="fas fa-boxes"></i> Available Assets</h4>
    <div class="row">
        @forelse($assets as $asset)
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
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

                        @if(isset($myBids[$asset->id]))
                        <dt class="col-sm-6">Your Last Bid:</dt>
                        <dd class="col-sm-6">
                            <span class="badge {{ $myBids[$asset->id]->amount == $asset->current_highest_bid ? 'bg-primary' : 'bg-warning text-dark' }}">
                                ${{ number_format($myBids[$asset->id]->amount, 2) }}
                                @if($myBids[$asset->id]->amount == $asset->current_highest_bid)
                                <i class="fas fa-crown"></i> Leading
                                @else
                                <i class="fas fa-arrow-up"></i> Outbid
                                @endif
                            </span>
                        </dd>
                        @endif
                    </dl>
                </div>
                <div class="card-footer">
                    @if(auth()->user()->bidder && auth()->user()->bidder->isApproved())
                    @if(isset($myBids[$asset->id]) && $myBids[$asset->id]->amount < $asset->current_highest_bid)
                        <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#bidModal{{ $asset->id }}">
                            <i class="fas fa-redo"></i> Place Higher Bid
                        </button>
                        @elseif(isset($myBids[$asset->id]) && $myBids[$asset->id]->amount == $asset->current_highest_bid)
                        <button type="button" class="btn btn-success w-100" disabled>
                            <i class="fas fa-crown"></i> You're Leading!
                        </button>
                        @else
                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#bidModal{{ $asset->id }}">
                            <i class="fas fa-hand-paper"></i> Place Bid
                        </button>
                        @endif
                        @elseif(!auth()->user()->bidder)
                        <a href="{{ route('bidder.register') }}" class="btn btn-warning w-100">
                            <i class="fas fa-user-plus"></i> Register to Bid
                        </a>
                        @else
                        <button class="btn btn-secondary w-100" disabled>
                            <i class="fas fa-clock"></i> Approval Pending
                        </button>
                        @endif
                </div>
            </div>
        </div>

        <!-- Bid Modal -->
        @if(auth()->user()->bidder && auth()->user()->bidder->isApproved())
        <div class="modal fade" id="bidModal{{ $asset->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-gavel"></i> Place Bid - {{ $asset->name }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('bidder.assets.bid', $asset) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            @if(isset($myBids[$asset->id]))
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Your Previous Bid:</strong> ${{ number_format($myBids[$asset->id]->amount, 2) }}
                                @if($myBids[$asset->id]->amount < $asset->current_highest_bid)
                                    <br><small class="text-danger">You've been outbid! Place a higher bid to compete.</small>
                                    @endif
                            </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label fw-bold">Current Highest Bid</label>
                                <input type="text" class="form-control" value="${{ number_format($asset->current_highest_bid, 2) }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Your {{ isset($myBids[$asset->id]) ? 'New' : '' }} Bid Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                        step="0.01" min="{{ $asset->current_highest_bid + 0.01 }}" required>
                                </div>
                                <small class="form-text text-muted">
                                    Minimum bid: ${{ number_format(max($event->cut_off_price ?? 0, $asset->current_highest_bid + ($event->bid_increment ?? 0.01)), 2) }}
                                </small>
                                @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Once submitted, your bid cannot be withdrawn.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Submit Bid
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
        @empty
        <div class="col-md-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No assets available for bidding in this event.
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection