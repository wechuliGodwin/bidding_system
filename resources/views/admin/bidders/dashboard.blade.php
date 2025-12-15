@extends('layouts.app')
@section('title', 'Bidder Dashboard')
@section('content')
<h2>Welcome, {{ auth()->user()->name }}</h2>

@if(!auth()->user()->bidder)
    <div class="alert alert-warning mt-4">
        <h5>Complete Your Registration</h5>
        <p>You need to complete your bidder registration before you can participate in bidding events.</p>
        <a href="{{ route('bidder.register') }}" class="btn btn-primary">Complete Registration</a>
    </div>
@elseif(auth()->user()->bidder->status === 'pending')
    <div class="alert alert-info mt-4">
        <h5>Registration Pending</h5>
        <p>Your registration is currently being reviewed by our administrators. You'll be notified once approved.</p>
    </div>
@elseif(auth()->user()->bidder->status === 'approved')
    <div class="alert alert-success mt-4">
        <h5>Account Approved!</h5>
        <p>Your account has been approved. You can now participate in bidding events.</p>
        <a href="{{ route('bidder.events.index') }}" class="btn btn-primary">View Active Events</a>
    </div>
@else
    <div class="alert alert-danger mt-4">
        <h5>Registration Rejected</h5>
        <p>Unfortunately, your registration was not approved. Please contact support for more information.</p>
    </div>
@endif

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Active Events</h5>
                <p class="card-text fs-3">{{ \App\Models\DisposalEvent::where('status', 'published')->count() }}</p>
                <a href="{{ route('bidder.events.index') }}" class="btn btn-primary btn-sm">View Events</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">My Bids</h5>
                <p class="card-text fs-3">
                    {{ auth()->user()->bidder ? auth()->user()->bidder->bids()->count() : 0 }}
                </p>
                <a href="{{ route('bidder.bids.index') }}" class="btn btn-primary btn-sm">View My Bids</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Winning Bids</h5>
                <p class="card-text fs-3">
                    {{ auth()->user()->bidder ? auth()->user()->bidder->bids()->where('status', 'winner')->count() : 0 }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection