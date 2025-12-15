@extends('layouts.app')

@section('title', 'Bidder Registration')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user-plus"></i> Register as Bidder</h4>
                </div>
                <div class="card-body">
                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <form action="{{ route('bidder.register.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="bidder_type" class="form-label">Bidder Type <span class="text-danger">*</span></label>
                            <select name="bidder_type" id="bidder_type" class="form-select @error('bidder_type') is-invalid @enderror" required>
                                <option value="">Select Type</option>
                                <option value="individual" {{ old('bidder_type') == 'individual' ? 'selected' : '' }}>Individual</option>
                                <option value="company" {{ old('bidder_type') == 'company' ? 'selected' : '' }}>Company</option>
                            </select>
                            @error('bidder_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="company-fields" style="display: none;">
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" name="company_name" id="company_name"
                                    class="form-control @error('company_name') is-invalid @enderror"
                                    value="{{ old('company_name') }}">
                                @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="registration_number" class="form-label">Registration Number</label>
                                <input type="text" name="registration_number" id="registration_number"
                                    class="form-control @error('registration_number') is-invalid @enderror"
                                    value="{{ old('registration_number') }}">
                                @error('registration_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" id="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone') }}" required>
                            @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea name="address" id="address" rows="3"
                                class="form-control @error('address') is-invalid @enderror"
                                required>{{ old('address') }}</textarea>
                            @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="documents" class="form-label">Upload Documents <span class="text-danger">*</span></label>
                            <input type="file" name="documents[]" id="documents"
                                class="form-control @error('documents') is-invalid @enderror @error('documents.*') is-invalid @enderror"
                                multiple accept=".pdf,.jpg,.jpeg,.png" required>
                            <small class="form-text text-muted">
                                Accepted formats: PDF, JPG, JPEG, PNG (Max 5MB per file)
                            </small>
                            @error('documents')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('documents.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Your application will be reviewed by an administrator. You will be notified once approved.
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Submit Registration
                            </button>
                            <a href="{{ route('bidder.dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('bidder_type').addEventListener('change', function() {
        const companyFields = document.getElementById('company-fields');
        if (this.value === 'company') {
            companyFields.style.display = 'block';
            document.getElementById('company_name').required = true;
            document.getElementById('registration_number').required = true;
        } else {
            companyFields.style.display = 'none';
            document.getElementById('company_name').required = false;
            document.getElementById('registration_number').required = false;
        }
    });

    // Trigger on page load if old value exists
    if (document.getElementById('bidder_type').value === 'company') {
        document.getElementById('company-fields').style.display = 'block';
    }
</script>
@endsection