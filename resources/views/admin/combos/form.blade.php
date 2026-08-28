@extends('admin.layouts.app')

@section('title', $product ? 'Edit Combo' : 'New Combo')
@section('page_title', $product ? 'Edit '.$product->name : 'Build a Combo')

@section('content')
<form action="{{ $product ? route('admin.combos.update', $product) : route('admin.combos.store') }}"
      method="POST" enctype="multipart/form-data">
    @csrf
    @if($product) @method('PUT') @endif

    <div class="row g-4">
        <div class="col-lg-8">
            {{-- 1. What is in the box, and what it costs --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-box2"></i> What is in the combo</h5>
                </div>
                <div class="card-body p-4">
                    <div
                        data-vue="ComboComposer"
                        data-props="{{ json_encode([
                            'options' => $options,
                            'components' => $components,
                            'price' => $price,
                            'comparePrice' => $comparePrice,
                            'error' => $errors->first('components') ?: $errors->first('price'),
                        ], JSON_UNESCAPED_UNICODE) }}"
                    ></div>
                </div>
            </div>

            {{-- 2. How it reads in the shop --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-type"></i> Title &amp; details</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Title (English) *</label>
                            <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror"
                                   value="{{ old('name_en', $product->name_en ?? '') }}"
                                   placeholder="Eid Gift Box" required>
                            @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">টাইটেল (বাংলা) *</label>
                            <input type="text" name="name_bn" class="form-control @error('name_bn') is-invalid @enderror"
                                   value="{{ old('name_bn', $product->name_bn ?? '') }}"
                                   placeholder="ঈদ গিফট বক্স" required>
                            @error('name_bn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Short description (English)</label>
                            <input type="text" name="short_description_en" class="form-control"
                                   value="{{ old('short_description_en', $product->short_description_en ?? '') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">সংক্ষিপ্ত বিবরণ (বাংলা)</label>
                            <input type="text" name="short_description_bn" class="form-control"
                                   value="{{ old('short_description_bn', $product->short_description_bn ?? '') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Full description (English)</label>
                            <textarea name="description_en" data-editor="basic" data-editor-height="260" rows="4" class="form-control">{{ old('description_en', $product->description_en ?? '') }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">বিস্তারিত বিবরণ (বাংলা)</label>
                            <textarea name="description_bn" data-editor="basic" data-editor-height="260" rows="4" class="form-control">{{ old('description_bn', $product->description_bn ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category *</label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                    @selected(old('category_id', $product->category_id ?? '') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                               @checked(old('is_active', $product->is_active ?? true))>
                        <label class="form-check-label" for="is_active">Visible in the shop</label>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark">Photos</h6>
                </div>
                <div class="card-body p-4">
                    <div
                        data-vue="ProductGallery"
                        data-props="{{ json_encode([
                            'existing' => $galleryImages,
                            'max' => \App\Models\Product::MAX_IMAGES,
                            'thumbnailId' => $thumbnailId,
                            'error' => $errors->first('images'),
                        ], JSON_UNESCAPED_UNICODE) }}"
                    ></div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 pt-3 border-top d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save"></i> {{ $product ? 'Update Combo' : 'Create Combo' }}
        </button>
        <a href="{{ route('admin.combos.index') }}" class="btn btn-light px-4">Cancel</a>

        @if($product)
            @can('combos.delete')
                <form action="{{ route('admin.combos.destroy', $product) }}" method="POST" class="ms-auto"
                      data-confirm="Delete this combo? The products inside are not affected.">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete Combo</button>
                </form>
            @endcan
        @endif
    </div>
</form>
@endsection
