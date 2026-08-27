@extends('admin.layouts.app')

@section('title', isset($unit) ? 'Edit Unit' : 'Add Unit')
@section('page_title', isset($unit) ? 'Edit Unit' : 'Add Unit')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ isset($unit) ? route('admin.units.update', $unit) : route('admin.units.store') }}" method="POST">
                    @csrf
                    @isset($unit) @method('PUT') @endisset

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Name (English) *</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $unit->name ?? '') }}" placeholder="Kilogram" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">নাম (বাংলা)</label>
                            <input type="text" name="name_bn" class="form-control @error('name_bn') is-invalid @enderror"
                                   value="{{ old('name_bn', $unit->name_bn ?? '') }}" placeholder="কেজি">
                            @error('name_bn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Short Code *</label>
                            <input type="text" name="short_code" class="form-control @error('short_code') is-invalid @enderror"
                                   value="{{ old('short_code', $unit->short_code ?? '') }}" placeholder="kg" required>
                            @error('short_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Shown next to quantities, e.g. 3 kg.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sort Order</label>
                            <input type="number" name="sort_order" min="0" class="form-control"
                                   value="{{ old('sort_order', $unit->sort_order ?? 0) }}">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                                       {{ old('is_active', $unit->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            {{ isset($unit) ? 'Update Unit' : 'Create Unit' }}
                        </button>
                        <a href="{{ route('admin.units.index') }}" class="btn btn-light px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
