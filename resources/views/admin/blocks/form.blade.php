@extends('admin.layouts.app')
@section('page_title', isset($block) ? 'Edit Item' : 'Add Item')

@section('content')
@php
    $groups = \App\Models\SiteBlock::GROUPS;
    $current = old('group', $block->group ?? $group);
    // The form renders every field, then shows only the ones the chosen group
    // declares — so switching the group re-shapes the form without a reload.
    $fieldsByGroup = collect($groups)->map(fn ($g) => $g['fields']);
@endphp

<div class="card admin-card">
    <div class="card-body p-4">
        <form action="{{ isset($block) ? route('admin.blocks.update', $block) : route('admin.blocks.store') }}"
              method="POST" enctype="multipart/form-data" id="block-form">
            @csrf
            @if(isset($block)) @method('PUT') @endif

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card bg-light border-0 p-3">
                        <label class="form-label fw-bold">List *</label>
                        <select name="group" id="block-group" class="form-select @error('group') is-invalid @enderror">
                            @foreach($groups as $key => $definition)
                                <option value="{{ $key }}" @selected($current === $key)>{{ $definition['label'] }}</option>
                            @endforeach
                        </select>
                        @error('group') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text mt-2" id="block-group-hint">{{ $groups[$current]['hint'] ?? '' }}</div>
                    </div>

                    <div class="card bg-light border-0 p-3 mt-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex align-items-center">
                                    <img src="https://flagcdn.com/w20/gb.png" class="me-2" alt="EN"> Title (English) *
                                </label>
                                <input type="text" name="title_en" class="form-control @error('title_en') is-invalid @enderror"
                                       value="{{ old('title_en', $block->title_en ?? '') }}" required>
                                @error('title_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex align-items-center">
                                    <img src="https://flagcdn.com/w20/bd.png" class="me-2" alt="BN"> টাইটেল (বাংলা)
                                </label>
                                <input type="text" name="title_bn" class="form-control @error('title_bn') is-invalid @enderror"
                                       value="{{ old('title_bn', $block->title_bn ?? '') }}">
                                @error('title_bn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="form-text mt-2">বাংলা খালি রাখলে ইংরেজিটাই দেখানো হবে।</div>
                    </div>

                    <div class="card bg-light border-0 p-3 mt-4" data-field="subtitle">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex align-items-center">
                                    <img src="https://flagcdn.com/w20/gb.png" class="me-2" alt="EN"> Second line (English)
                                </label>
                                <input type="text" name="subtitle_en" class="form-control @error('subtitle_en') is-invalid @enderror"
                                       value="{{ old('subtitle_en', $block->subtitle_en ?? '') }}">
                                @error('subtitle_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex align-items-center">
                                    <img src="https://flagcdn.com/w20/bd.png" class="me-2" alt="BN"> দ্বিতীয় লাইন (বাংলা)
                                </label>
                                <input type="text" name="subtitle_bn" class="form-control @error('subtitle_bn') is-invalid @enderror"
                                       value="{{ old('subtitle_bn', $block->subtitle_bn ?? '') }}">
                                @error('subtitle_bn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="form-text mt-2">
                            সার্ভিস কার্ডে এটি ছোট বিবরণ, প্রোমো কার্ডে উপরের ছোট সোনালি লেখা।
                        </div>
                    </div>

                    <div class="card bg-light border-0 p-3 mt-4" data-field="url">
                        <label class="form-label fw-bold">Link</label>
                        <input type="text" name="url" class="form-control @error('url') is-invalid @enderror"
                               value="{{ old('url', $block->url ?? '') }}" placeholder="/shop?category=toys-games">
                        @error('url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">
                            সাইটের ভেতরের ঠিকানা <code>/shop</code> এভাবে, বাইরের হলে পুরো
                            <code>https://…</code>। খালি রাখলে হোমপেজে যাবে।
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3" data-field="icon">
                        <label class="form-label fw-bold">Icon</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-{{ old('icon', $block->icon ?? null) ?: 'chevron-right' }}" id="icon-preview"></i></span>
                            <input type="text" name="icon" class="form-control @error('icon') is-invalid @enderror"
                                   value="{{ old('icon', $block->icon ?? '') }}" placeholder="truck" id="icon-input">
                            @error('icon') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">
                            <a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener">Bootstrap Icons</a>-এর নাম,
                            <code>bi-</code> ছাড়া। যেমন <code>truck</code>, <code>cash-coin</code>, <code>headset</code>।
                        </div>
                    </div>

                    <div class="mb-3" data-field="image">
                        <label class="form-label fw-bold">Background Image</label>
                        @if(isset($block) && $block->image_url)
                            <div class="mb-2">
                                <img src="{{ $block->image_url }}" alt="" style="max-width:100%; border-radius:8px;">
                            </div>
                        @endif
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">JPG, PNG or WebP, up to 2 MB. না দিলে সবুজ ব্যাকগ্রাউন্ড বসবে।</div>
                    </div>

                    <div class="mb-3" data-field="highlight">
                        <label class="form-label fw-bold">Highlight</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_highlighted" value="0">
                            <input class="form-check-input" type="checkbox" name="is_highlighted" value="1"
                                   {{ old('is_highlighted', $block->is_highlighted ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label">সোনালি রঙে দেখাও</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Sort Order</label>
                        <input type="number" name="sort_order" min="0"
                               class="form-control @error('sort_order') is-invalid @enderror"
                               value="{{ old('sort_order', $block->sort_order ?? 0) }}">
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">ছোট সংখ্যা আগে দেখাবে।</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', $block->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 mt-3">
                        <i class="bi bi-check-circle"></i> {{ isset($block) ? 'Update Item' : 'Create Item' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script type="application/json" id="block-fields">@json($fieldsByGroup)</script>
<script type="application/json" id="block-hints">@json(collect($groups)->map(fn ($g) => $g['hint']))</script>
<script>
    // Show only the inputs the selected list actually uses. The server enforces
    // the same rule, so a hidden field can never reach the database.
    (function () {
        const form = document.getElementById('block-form');
        const select = document.getElementById('block-group');
        const hint = document.getElementById('block-group-hint');
        const fields = JSON.parse(document.getElementById('block-fields').textContent);
        const hints = JSON.parse(document.getElementById('block-hints').textContent);

        function sync() {
            const active = fields[select.value] || [];
            form.querySelectorAll('[data-field]').forEach((el) => {
                el.hidden = !active.includes(el.dataset.field);
            });
            hint.textContent = hints[select.value] || '';
        }

        select.addEventListener('change', sync);
        sync();

        const icon = document.getElementById('icon-input');
        const preview = document.getElementById('icon-preview');
        icon?.addEventListener('input', () => {
            preview.className = 'bi bi-' + (icon.value.trim() || 'chevron-right');
        });
    })();
</script>
@endpush
@endsection
