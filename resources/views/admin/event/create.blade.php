@extends('layouts.app')
@section('title', 'Create Disposal Event')
@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h4>Create Disposal Event</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.events.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Event Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bid Type</label>
                        <select name="bid_type" class="form-control" required>
                            <option value="highest_wins">Highest Bid Wins</option>
                            <option value="lowest_wins">Lowest Bid Wins</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cut-off Price (Minimum Bid)</label>
                        <input type="number" name="cut_off_price" class="form-control" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bid Increment</label>
                        <input type="number" name="bid_increment" class="form-control" step="0.01">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date & Time</label>
                            <input type="datetime-local" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date & Time</label>
                            <input type="datetime-local" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.events.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection