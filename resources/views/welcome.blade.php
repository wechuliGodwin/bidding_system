@extends('layouts.app')
@section('title', 'Welcome')
@section('content')
<div class="text-center py-5">
    <h1 class="display-4 mb-4">Welcome to Bidding System</h1>
    <p class="lead mb-4">A complete platform for managing disposal events and competitive bidding</p>
    
    @guest
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Login</a>
            <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg">Register</a>
        </div>
    @else
        @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-lg">Go to Admin Dashboard</a>
        @else
            <a href="{{ route('bidder.dashboard') }}" class="btn btn-primary btn-lg">Go to Dashboard</a>
        @endif
    @endguest

    <div class="row mt-5">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <i class="fas fa-gavel fa-3x text-primary mb-3"></i>
                    <h5>Real-Time Bidding</h5>
                    <p>Participate in live bidding events with real-time updates</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                    <h5>Secure Platform</h5>
                    <p>Verified bidders and transparent bidding process</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                    <h5>Track Your Bids</h5>
                    <p>Monitor your bidding history and winning status</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection