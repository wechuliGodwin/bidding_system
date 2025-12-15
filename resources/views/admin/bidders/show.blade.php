@extends('layouts.app')
@section('title', 'Bidder Details')
@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h4>Bidder Details</h4>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Name:</dt>
                    <dd class="col-sm-8">{{ $bidder->user->name }}</dd>

                    <dt class="col-sm-4">Email:</dt>
                    <dd class="col-sm-8">{{ $bidder->user->email }}</dd>

                    <dt class="col-sm-4">Type:</dt>
                    <dd class="col-sm-8">{{ ucfirst($bidder->bidder_type) }}</dd>

                    @if($bidder->bidder_type === 'company')
                        <dt class="col-sm-4">Company Name:</dt>
                        <dd class="col-sm-8">{{ $bidder->company_name }}</dd>

                        <dt class="col-sm-4">Registration Number:</dt>
                        <dd class="col-sm-8">{{ $bidder->registration_number }}</dd>
                    @endif

                    <dt class="col-sm-4">Phone:</dt>
                    <dd class="col-sm-8">{{ $bidder->phone }}</dd>

                    <dt class="col-sm-4">Address:</dt>
                    <dd class="col-sm-8">{{ $bidder->address }}</dd>

                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-{{ $bidder->status === 'approved' ? 'success' : ($bidder->status === 'pending' ? 'warning' : 'danger') }}">
                            {{ ucfirst($bidder->status) }}
                        </span>
                    </dd>
                </dl>

                <h5 class="mt-4">Documents</h5>
                <ul class="list-group">
                    @forelse($bidder->documents as $document)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $document->document_type }}
                            <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="btn btn-sm btn-primary">View</a>
                        </li>
                    @empty
                        <li class="list-group-item">No documents uploaded</li>
                    @endforelse
                </ul>

                <div class="mt-4">
                    <a href="{{ route('admin.bidders.index') }}" class="btn btn-secondary">Back</a>
                    @if($bidder->status === 'pending')
                        <form action="{{ route('admin.bidders.approve', $bidder) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success">Approve</button>
                        </form>
                        <form action="{{ route('admin.bidders.reject', $bidder) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-danger">Reject</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection