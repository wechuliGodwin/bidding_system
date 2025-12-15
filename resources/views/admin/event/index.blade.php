<?php
// resources/views/admin/events/index.blade.php
?>
@extends('layouts.app')
@section('title', 'Disposal Events')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Disposal Events</h2>
    <a href="{{ route('admin.events.create') }}" class="btn btn-primary">Create New Event</a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Assets</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $event)
                <tr>
                    <td>{{ $event->name }}</td>
                    <td>{{ $event->start_date->format('M d, Y H:i') }}</td>
                    <td>{{ $event->end_date->format('M d, Y H:i') }}</td>
                    <td>
                        <span class="badge bg-{{ $event->status === 'published' ? 'success' : 'secondary' }}">
                            {{ ucfirst($event->status) }}
                        </span>
                    </td>
                    <td>{{ $event->assets->count() }}</td>
                    <td>
                        <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-sm btn-warning">Edit</a>
                        <a href="{{ route('admin.assets.create', $event) }}" class="btn btn-sm btn-info">Add Asset</a>
                        @if($event->status === 'draft')
                            <form action="{{ route('admin.events.publish', $event) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">Publish</button>
                            </form>
                        @endif
                        @if($event->status === 'published' && $event->hasEnded())
                            <form action="{{ route('admin.events.close', $event) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger">Close & Select Winners</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center">No events found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $events->links() }}
    </div>
</div>
@endsection