<?php
// ========== ADMIN VIEWS ==========

// resources/views/admin/dashboard.blade.php
?>
@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('content')
<div class="row">
    <div class="col-md-12">
        <h2>Admin Dashboard</h2>
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Events</h5>
                        <p class="card-text fs-3">{{ \App\Models\DisposalEvent::count() }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Active Events</h5>
                        <p class="card-text fs-3">{{ \App\Models\DisposalEvent::where('status', 'published')->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Pending Bidders</h5>
                        <p class="card-text fs-3">{{ \App\Models\Bidder::where('status', 'pending')->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
