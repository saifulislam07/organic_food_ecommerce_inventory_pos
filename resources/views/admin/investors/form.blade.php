@extends('admin.layouts.app')

@section('title', $investor ? 'Edit Investor' : 'Add Investor')
@section('page_title', $investor ? 'Edit Investor' : 'Add New Investor')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ $investor ? route('admin.investors.update', $investor) : route('admin.investors.store') }}"
                      method="POST">
                    @csrf
                    @if($investor) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $investor->name ?? '') }}"
                                   placeholder="e.g., Saiful Islam" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Phone</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $investor->phone ?? '') }}" placeholder="01XXXXXXXXX">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $investor->email ?? '') }}">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="3"
                                      placeholder="Partnership terms, agreement date, anything worth remembering">{{ old('notes', $investor->notes ?? '') }}</textarea>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                       @checked(old('is_active', $investor->is_active ?? true))>
                                <label class="form-check-label fw-bold">Active</label>
                            </div>
                            <div class="form-text">
                                Someone paid out in full can be switched off. They keep their history but
                                stop appearing when you record new money.
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            {{ $investor ? 'Update Investor' : 'Save Investor' }}
                        </button>
                        <a href="{{ route('admin.investors.index') }}" class="btn btn-light px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
