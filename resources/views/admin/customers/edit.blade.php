@extends('admin.layouts.app')

@section('title', 'Edit Customer')
@section('page_title', 'Edit '.$customer->name)

@section('content')
<form action="{{ route('admin.customers.update', $customer) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark">Customer Details</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Name *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $customer->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Mobile *</label>
                        <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror"
                               value="{{ old('mobile', $customer->mobile) }}" placeholder="01712345678" required>
                        @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Used to sign in and to match guest orders to this account.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $customer->email) }}" autocomplete="off">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="form-label fw-bold">New Password</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                               autocomplete="new-password">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Leave blank to keep the current password.</div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Save Changes
                </button>
                <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-light">Cancel</a>
            </div>
        </div>
    </div>
</form>
@endsection
