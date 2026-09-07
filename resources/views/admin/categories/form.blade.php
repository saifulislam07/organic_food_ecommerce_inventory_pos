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
                    <div class="card bg-light border-0 p-3">
                        <div class="mb-3">
                            <label class="form-label fw-bold d-flex align-items-center">
                                <img src="https://flagcdn.com/w20/gb.png" class="me-2" alt="EN"> Name (English) *
                            </label>
                            <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror"
                                   value="{{ old('name_en', $category->name_en ?? '') }}" required>
                            @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="form-label fw-bold d-flex align-items-center">
                                <img src="https://flagcdn.com/w20/bd.png" class="me-2" alt="BN"> নাম (বাংলা) *
                            </label>
                            <input type="text" name="name_bn" class="form-control @error('name_bn') is-invalid @enderror"
                                   value="{{ old('name_bn', $category->name_bn ?? '') }}" required>
                            @error('name_bn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{--
                        The starting draft every promotion in this category
                        opens with.

                        The same island the campaign form uses, minus the
                        reviews: a testimonial belongs to a product, not to a
                        shelf of them. It is copied into a page on an explicit
                        click over there, so editing it here never touches a
                        campaign that is already running.
                    --}}
                    <div class="card bg-light border-0 p-3 mt-4">
                        <h6 class="fw-bold mb-1"><i class="bi bi-magic"></i> Landing Page Defaults</h6>
                        <p class="text-muted small mb-3">
                            নতুন প্রোমোশন বানানোর সময় এক ক্লিকে এগুলো বসানো যাবে।
                            খালি রাখলে কিছুই প্রস্তাব করা হবে না।
                        </p>

                        <div class="mb-3">
                            <label class="form-label fw-bold">অর্ডার বাটনের লেখা</label>
                            <input type="text" name="cta_text" class="form-control" maxlength="100"
                                   value="{{ $draft['cta_text'] }}" placeholder="অর্ডার করুন">
                        </div>

                        <div
                            data-vue="LandingContentBlocks"
                            data-props="{{ json_encode([
                                'blocks' => \App\Models\LandingPage::BLOCKS,
                                'sections' => $draft['sections'],
                                'features' => $draft['features'],
                                'faqs' => $draft['faqs'],
                                'specs' => $draft['specs'],
                                'withReviews' => false,
                            ], JSON_UNESCAPED_UNICODE) }}"
                        ></div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Image</label>
                        @if(isset($category) && $category->image)
                            <div class="mb-2"><img src="{{ $category->image_url }}" alt="" style="max-width:100%; border-radius:8px;"></div>
                        @endif
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">JPG, PNG or WebP, up to 2 MB.</div>
                    </div>

                    {{-- The look every landing page in this category inherits.
                         Set here rather than per campaign so a new promotion is
                         one dropdown, not a colour decision. --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">Landing Page Theme</label>
                        <select name="theme" class="form-select @error('theme') is-invalid @enderror" data-cat-theme>
                            <option value="">Brand default</option>
                            @foreach(\App\Models\LandingPage::THEMES as $key => $label)
                                @continue($key === 'default')
                                <option value="{{ $key }}" @selected(old('theme', $category->theme ?? '') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('theme') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <div class="lp-swatch d-flex align-items-center gap-2 border rounded p-2 mt-2" data-cat-swatch>
                            <span class="lp-swatch-dot" style="background: var(--primary);"></span>
                            <span class="lp-swatch-dot" style="background: var(--accent);"></span>
                            <span class="lp-swatch-dot" style="background: var(--cream-dark);"></span>
                            <span class="small text-muted ms-1">Colours a campaign page in this category will use.</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Sort Order</label>
                        <input type="number" name="sort_order" min="0"
                               class="form-control @error('sort_order') is-invalid @enderror"
                               value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

@push('styles')
    <link href="{{ asset('css/landing-themes.css') }}" rel="stylesheet">
    <style>
        .lp-swatch-dot {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, .12);
            flex: none;
        }
    </style>
@endpush

@push('scripts')
<script>
(function () {
    const select = document.querySelector('[data-cat-theme]');
    const swatch = document.querySelector('[data-cat-swatch]');

    if (! select || ! swatch) {
        return;
    }

    function paint() {
        swatch.className = swatch.className.replace(/\blp-theme-\S+/g, '').trim()
            + ' lp-theme-' + (select.value || 'default');
    }

    select.addEventListener('change', paint);
    paint();
})();
</script>
@endpush
