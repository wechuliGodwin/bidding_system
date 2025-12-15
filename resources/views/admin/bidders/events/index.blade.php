@extends('layouts.app')
@section('title', 'Active Events')
@section('content')
<h2 class="mb-4">Active Bidding Events</h2>

<div class="row">
    @forelse($events as $event)
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">{{ $event->name }}</h5>
            </div>
            <div class="card-body">
                <p>{{ $event->description }}</p>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Start:</dt>
                    <dd class="col-sm-8">{{ $event->start_date->format('M d, Y H:i') }}</dd>

                    <dt class="col-sm-4">End:</dt>
                    <dd class="col-sm-8">{{ $event->end_date->format('M d, Y H:i') }}</dd>

                    <dt class="col-sm-4">Assets:</dt>
                    <dd class="col-sm-8">{{ $event->assets->count() }} items</dd>
                </dl>
            </div>
            <div class="card-footer">
                <a href="{{ route('bidder.events.show', $event) }}" class="btn btn-primary">View Assets & Bid</a>
            </div>
        </div>
    </div>
    @empty
    <div class="col-md-12">
        <div class="alert alert-info">No active events at the moment. Check back later!</div>
    </div>
    @endforelse
</div>
@endsection