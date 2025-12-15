@extends('layouts.app')
@section('title', 'Active Events')
@section('content')
<div class="container py-4">
    <h2 class="mb-4"><i class="fas fa-calendar-alt"></i> Active Bidding Events</h2>

    <div class="row">
        @forelse($events as $event)
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">{{ $event->name }}</h5>
                </div>
                <div class="card-body">
                    <p>{{ $event->description }}</p>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Bid Type:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $event->bid_type)) }}</span>
                        </dd>

                        <dt class="col-sm-4">Start:</dt>
                        <dd class="col-sm-8">{{ $event->start_date->format('M d, Y H:i') }}</dd>

                        <dt class="col-sm-4">End:</dt>
                        <dd class="col-sm-8">{{ $event->end_date->format('M d, Y H:i') }}</dd>

                        <dt class="col-sm-4">Assets:</dt>
                        <dd class="col-sm-8">{{ $event->assets->count() }} items</dd>

                        @if($event->cut_off_price)
                        <dt class="col-sm-4">Min Bid:</dt>
                        <dd class="col-sm-8">{{ number_format($event->cut_off_price, 2) }}</dd>
                        @endif
                    </dl>
                </div>
                <div class="card-footer">
                    <a href="{{ route('bidder.events.show', $event) }}" class="btn btn-primary">
                        <i class="fas fa-eye"></i> View Assets & Bid
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-md-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No active events at the moment. Check back later!
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection