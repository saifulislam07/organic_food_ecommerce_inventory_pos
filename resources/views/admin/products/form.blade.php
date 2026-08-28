@extends('admin.layouts.app')
@section('page_title', isset($product) ? 'Edit Product' : 'Add Product')

@section('content')
<div class="card admin-card">
    <div class="card-body p-4">
        <form action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}"
              method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($product)) @method('PUT') @endif

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- English Details -->
                    <div class="card bg-light border-0 p-3 mb-4">
                        <h6 class="fw-bold mb-3 d-flex align-items-center"><img src="https://flagcdn.com/w20/gb.png" class="me-2" alt="EN"> English Details</h6>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Name (English) *</label>
                            <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror"
                                   value="{{ old('name_en', $product->name_en ?? '') }}" required>
                            @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Short Description (English)</label>
                            <input type="text" name="short_description_en" class="form-control"
                                   value="{{ old('short_description_en', $product->short_description_en ?? '') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Full Description (English)</label>
                            <textarea name="description_en" data-editor="basic" data-editor-height="260" class="form-control" rows="4">{{ old('description_en', $product->description_en ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- Bengali Details -->
                    <div class="card bg-light border-0 p-3 mb-4">
                        <h6 class="fw-bold mb-3 d-flex align-items-center"><img src="https://flagcdn.com/w20/bd.png" class="me-2" alt="BN"> Bengali Details (বাংলা) *</h6>
                        <div class="mb-3">
                            <label class="form-label fw-bold">নাম (বাংলা) *</label>
                            <input type="text" name="name_bn" class="form-control @error('name_bn') is-invalid @enderror"
                                   value="{{ old('name_bn', $product->name_bn ?? '') }}" required>
                            @error('name_bn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">সংক্ষিপ্ত বিবরণ (বাংলা)</label>
                            <input type="text" name="short_description_bn" class="form-control"
                                   value="{{ old('short_description_bn', $product->short_description_bn ?? '') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">বিস্তারিত বিবরণ (বাংলা)</label>
                            <textarea name="description_bn" data-editor="basic" data-editor-height="260" class="form-control" rows="4">{{ old('description_bn', $product->description_bn ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- Variants -->
                    @php
                        $variantRows = old('variants', isset($product)
                            ? $product->variants->map(fn ($v) => [
                                'name' => $v->name,
                                'unit_id' => $v->unit_id,
                                'unit_value' => $v->unit_value,
                                'price' => $v->price,
                                'sale_price' => $v->sale_price,
                                'stock' => $v->stock,
                            ])->values()->all()
                            : []);
                    @endphp
                    <div class="mb-3">
                        <label class="form-label fw-bold">Product Variants *</label>
                        <div
                            data-vue="VariantRepeater"
                            data-props="{{ json_encode([
                                'rows' => $variantRows,
                                'units' => $units,
                                'errors' => $errors->toArray(),
                            ], JSON_UNESCAPED_UNICODE) }}"
                        ></div>
                        @error('variants') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- SEO -->
                    <div class="border-top pt-3 mt-3">
                        <h6 class="fw-bold mb-3"><i class="bi bi-search"></i> SEO Settings</h6>
                        <div class="mb-3">
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="meta_title" class="form-control"
                                   value="{{ old('meta_title', $product->meta_title ?? '') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="2">{{ old('meta_description', $product->meta_description ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category *</label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id ?? '') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Product Images
                            <span class="text-muted fw-normal small">(max {{ \App\Models\Product::MAX_IMAGES }})</span>
                        </label>
                        @php
                            $galleryImages = isset($product)
                                ? $product->images->map(fn ($img) => [
                                    'id' => $img->id,
                                    'url' => $img->url,
                                ])->values()
                                : collect();

                            $currentThumbnailId = isset($product)
                                ? optional($product->images->firstWhere('path', $product->getRawOriginal('image')))->id
                                : null;
                        @endphp
                        <div
                            data-vue="ProductGallery"
                            data-props="{{ json_encode([
                                'existing' => $galleryImages,
                                'max' => \App\Models\Product::MAX_IMAGES,
                                'thumbnailId' => $currentThumbnailId,
                                'error' => $errors->first('images') ?: $errors->first('images.0'),
                            ], JSON_UNESCAPED_UNICODE) }}"
                        ></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label">Active</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_featured" value="0">
                            <input class="form-check-input" type="checkbox" name="is_featured" value="1"
                                   {{ old('is_featured', $product->is_featured ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label">Featured</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_bestseller" value="0">
                            <input class="form-check-input" type="checkbox" name="is_bestseller" value="1"
                                   {{ old('is_bestseller', $product->is_bestseller ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label">Best Seller</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_trending" value="0">
                            <input class="form-check-input" type="checkbox" name="is_trending" value="1"
                                   {{ old('is_trending', $product->is_trending ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label">Trending</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_preorder" value="0">
                            <input class="form-check-input" type="checkbox" name="is_preorder" value="1"
                                   {{ old('is_preorder', $product->is_preorder ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label">Pre-order</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 mt-3">
                        <i class="bi bi-check-circle"></i> {{ isset($product) ? 'Update Product' : 'Create Product' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
