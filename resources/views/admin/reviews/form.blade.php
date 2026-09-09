@extends('admin.layouts.app')
@section('page_title', isset($review) ? 'Edit Review' : 'Add Review')

@section('content')
<div class="card admin-card">
    <div class="card-body p-4">
        <form action="{{ isset($review) ? route('admin.reviews.update', $review) : route('admin.reviews.store') }}"
              method="POST">
            @csrf
            @if(isset($review)) @method('PUT') @endif

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Product *</label>
                            <select name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                                <option value="">Select a product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}"
                                        @selected(old('product_id', $review->product_id ?? '') == $product->id)>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Customer Name *</label>
                            <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror"
                                   value="{{ old('customer_name', $review->customer_name ?? '') }}" required>
                            @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @if(isset($review) && $review->user_id)
                                <div class="form-text">Linked to a registered customer account.</div>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Rating *</label>
                            <select name="rating" class="form-select @error('rating') is-invalid @enderror" required>
                                @for($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}" @selected((int) old('rating', $review->rating ?? 5) === $i)>
                                        {{ $i }} — {{ str_repeat('★', $i) }}{{ str_repeat('☆', 5 - $i) }}
                                    </option>
                                @endfor
                            </select>
                            @error('rating') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Title</label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title', $review->title ?? '') }}" placeholder="Great quality!">
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-bold">Review *</label>
                        <textarea name="body" rows="5" class="form-control @error('body') is-invalid @enderror"
                                  required>{{ old('body', $review->body ?? '') }}</textarea>
                        @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_approved" value="0">
                            <input class="form-check-input" type="checkbox" name="is_approved" value="1"
                                   {{ old('is_approved', $review->is_approved ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label">Approved (visible on the site)</label>
                        </div>
                        @if(isset($review) && $review->order_id)
                            <div class="form-text mt-2"><i class="bi bi-patch-check"></i> Verified purchase.</div>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-success w-100 mt-3">
                        <i class="bi bi-check-circle"></i> {{ isset($review) ? 'Update Review' : 'Create Review' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
