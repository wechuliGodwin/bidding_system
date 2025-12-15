@extends('layouts.app')
@section('title', 'Add Asset')
@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h4>Add Asset to {{ $event->name }}</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.assets.store', $event) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Asset Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Starting Price</label>
                        <input type="number" name="starting_price" class="form-control" step="0.01" required>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.events.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection