@extends('admin.layouts.app')
@section('page_title', isset($slide) ? 'Edit Slide' : 'Add Slide')

@section('content')
<div class="card admin-card">
    <div class="card-body p-4">
        <form action="{{ isset($slide) ? route('admin.sliders.update', $slide) : route('admin.sliders.store') }}"
              method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($slide)) @method('PUT') @endif

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card bg-light border-0 p-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex align-items-center">
                                    <img src="https://flagcdn.com/w20/gb.png" class="me-2" alt="EN"> Badge (English)
                                </label>
                                <input type="text" name="badge_en" class="form-control @error('badge_en') is-invalid @enderror"
                                       value="{{ old('badge_en', $slide->badge_en ?? '') }}" placeholder="New Arrivals for Little Ones">
                                @error('badge_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex align-items-center">
                                    <img src="https://flagcdn.com/w20/bd.png" class="me-2" alt="BN"> ব্যাজ (বাংলা)
                                </label>
                                <input type="text" name="badge_bn" class="form-control @error('badge_bn') is-invalid @enderror"
                                       value="{{ old('badge_bn', $slide->badge_bn ?? '') }}" placeholder="ছোট্ট সোনামণিদের জন্য নতুন সংগ্রহ">
                                @error('badge_bn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border-0 p-3 mt-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex align-items-center">
                                    <img src="https://flagcdn.com/w20/gb.png" class="me-2" alt="EN"> Title (English) *
                                </label>
                                <input type="text" name="title_en" class="form-control @error('title_en') is-invalid @enderror"
                                       value="{{ old('title_en', $slide->title_en ?? '') }}" required>
                                @error('title_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex align-items-center">
                                    <img src="https://flagcdn.com/w20/bd.png" class="me-2" alt="BN"> টাইটেল (বাংলা)
                                </label>
                                <input type="text" name="title_bn" class="form-control @error('title_bn') is-invalid @enderror"
                                       value="{{ old('title_bn', $slide->title_bn ?? '') }}">
                                @error('title_bn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="form-text mt-2">
                            লাইন ভাঙতে <code>&lt;br&gt;</code> আর একটি শব্দ সোনালি রঙে দেখাতে
                            <code>&lt;span&gt;শব্দ&lt;/span&gt;</code> ব্যবহার করা যাবে।
                            বাংলা খালি রাখলে ইংরেজিটাই দেখানো হবে।
                        </div>
                    </div>

                    <div class="card bg-light border-0 p-3 mt-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex align-items-center">
                                    <img src="https://flagcdn.com/w20/gb.png" class="me-2" alt="EN"> Description (English)
                                </label>
                                <textarea name="subtitle_en" rows="3"
                                          class="form-control @error('subtitle_en') is-invalid @enderror">{{ old('subtitle_en', $slide->subtitle_en ?? '') }}</textarea>
                                @error('subtitle_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex align-items-center">
                                    <img src="https://flagcdn.com/w20/bd.png" class="me-2" alt="BN"> বিবরণ (বাংলা)
                                </label>
                                <textarea name="subtitle_bn" rows="3"
                                          class="form-control @error('subtitle_bn') is-invalid @enderror">{{ old('subtitle_bn', $slide->subtitle_bn ?? '') }}</textarea>
                                @error('subtitle_bn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border-0 p-3 mt-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-cursor"></i> Button</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Button Text (EN)</label>
                                <input type="text" name="button_text_en" class="form-control @error('button_text_en') is-invalid @enderror"
                                       value="{{ old('button_text_en', $slide->button_text_en ?? '') }}" placeholder="Shop Now">
                                @error('button_text_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">বাটনের লেখা (BN)</label>
                                <input type="text" name="button_text_bn" class="form-control @error('button_text_bn') is-invalid @enderror"
                                       value="{{ old('button_text_bn', $slide->button_text_bn ?? '') }}" placeholder="শপ করুন">
                                @error('button_text_bn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Link</label>
                                <input type="text" name="button_url" class="form-control @error('button_url') is-invalid @enderror"
                                       value="{{ old('button_url', $slide->button_url ?? '') }}" placeholder="/shop?category=toys-games">
                                @error('button_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">খালি রাখলে শপ পেজে যাবে।</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Slide Image</label>
                        <div class="mb-2">
                            <img src="{{ isset($slide) ? $slide->image_url : asset(\App\Models\HeroSlide::DEFAULT_IMAGE) }}"
                                 alt="" style="max-width:100%; border-radius:8px;">
                        </div>
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">JPG, PNG or WebP, up to 2 MB. না দিলে ডিফল্ট ছবিটি বসবে।</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Sort Order</label>
                        <input type="number" name="sort_order" min="0"
                               class="form-control @error('sort_order') is-invalid @enderror"
                               value="{{ old('sort_order', $slide->sort_order ?? 0) }}">
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">ছোট সংখ্যা আগে দেখাবে।</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', $slide->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 mt-3">
                        <i class="bi bi-check-circle"></i> {{ isset($slide) ? 'Update Slide' : 'Create Slide' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
