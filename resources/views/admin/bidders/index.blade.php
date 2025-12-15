@extends('layouts.app')
@section('title', 'Bidders')
@section('content')
<h2 class="mb-4">Registered Bidders</h2>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bidders as $bidder)
                <tr>
                    <td>{{ $bidder->user->name }}</td>
                    <td>{{ $bidder->user->email }}</td>
                    <td>{{ ucfirst($bidder->bidder_type) }}</td>
                    <td>{{ $bidder->phone }}</td>
                    <td>
                        <span class="badge bg-{{ $bidder->status === 'approved' ? 'success' : ($bidder->status === 'pending' ? 'warning' : 'danger') }}">
                            {{ ucfirst($bidder->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.bidders.show', $bidder) }}" class="btn btn-sm btn-info">View</a>
                        @if($bidder->status === 'pending')
                            <form action="{{ route('admin.bidders.approve', $bidder) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                            </form>
                            <form action="{{ route('admin.bidders.reject', $bidder) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center">No bidders found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $bidders->links() }}
    </div>
</div>
@endsection