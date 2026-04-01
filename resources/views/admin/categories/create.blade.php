@extends('admin.layouts.app')
@section('page_title', isset($category) ? 'Edit Category' : 'Add Category')

@section('content')
<div class="card admin-card">
    <div class="card-body p-4">
        <form action="{{ isset($category) ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
              method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($category)) @method('PUT') @endif

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- English Details -->
                    <div class="card bg-light border-0 p-3 mb-4">
                        <h6 class="fw-bold mb-3 d-flex align-items-center"><img src="https://flagcdn.com/w20/gb.png" class="me-2" alt="EN"> English Details</h6>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Name (English) *</label>
                            <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror"
                                   value="{{ old('name_en', $category->name_en ?? '') }}" required>
                            @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description (English)</label>
                            <textarea name="description_en" class="form-control" rows="4">{{ old('description_en', $category->description_en ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- Bengali Details -->
                    <div class="card bg-light border-0 p-3 mb-4">
                        <h6 class="fw-bold mb-3 d-flex align-items-center"><img src="https://flagcdn.com/w20/bd.png" class="me-2" alt="BN"> Bengali Details (বাংলা) *</h6>
                        <div class="mb-3">
                            <label class="form-label fw-bold">নাম (বাংলা) *</label>
                            <input type="text" name="name_bn" class="form-control @error('name_bn') is-invalid @enderror"
                                   value="{{ old('name_bn', $category->name_bn ?? '') }}" required>
                            @error('name_bn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">বিবরণ (বাংলা)</label>
                            <textarea name="description_bn" class="form-control" rows="4">{{ old('description_bn', $category->description_bn ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Image</label>
                        @if(isset($category) && $category->image)
                            <div class="mb-2"><img src="{{ $category->image_url }}" alt="" style="max-width:100%; border-radius:8px;"></div>
                        @endif
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control"
                               value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 mt-3">
                        <i class="bi bi-check-circle"></i> {{ isset($category) ? 'Update Category' : 'Create Category' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
