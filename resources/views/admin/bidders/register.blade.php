@extends('layouts.app')
@section('title', 'Bidder Registration')
@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h4>Bidder Registration</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('bidder.register.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Bidder Type</label>
                        <select name="bidder_type" id="bidder_type" class="form-control" required>
                            <option value="individual">Individual</option>
                            <option value="company">Company</option>
                        </select>
                    </div>
                    <div id="company_fields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Registration Number</label>
                            <input type="text" name="registration_number" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload Documents (ID, Registration Cert, etc.)</label>
                        <input type="file" name="documents[]" class="form-control" multiple required>
                        <small class="form-text text-muted">You can upload multiple documents. Max 5MB per file.</small>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('bidder.dashboard') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Submit Registration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('bidder_type').addEventListener('change', function() {
    const companyFields = document.getElementById('company_fields');
    companyFields.style.display = this.value === 'company' ? 'block' : 'none';
});
</script>
@endpush