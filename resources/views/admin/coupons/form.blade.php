@extends('admin.layouts.app')
@section('page_title', isset($coupon) ? 'Edit Coupon' : 'Add Coupon')

@section('content')
@php
    $type = old('type', $coupon->type ?? \App\Models\Coupon::TYPE_PERCENT);
    $scope = old('applies_to', $coupon->applies_to ?? 'all');
    $pickedCategories = collect(old('category_ids', isset($coupon) ? $coupon->categories->pluck('id')->all() : []))
        ->map(fn ($id) => (int) $id);
    $pickedProducts = collect(old('product_ids', isset($coupon) ? $coupon->products->pluck('id')->all() : []))
        ->map(fn ($id) => (int) $id);
@endphp

<div class="card admin-card">
    <div class="card-body p-4">
        <form action="{{ isset($coupon) ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}"
              method="POST" id="coupon-form">
            @csrf
            @if(isset($coupon)) @method('PUT') @endif

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card bg-light border-0 p-3">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Code *</label>
                                <input type="text" name="code"
                                       class="form-control text-uppercase font-monospace @error('code') is-invalid @enderror"
                                       value="{{ old('code', $coupon->code ?? '') }}" placeholder="EID25" required>
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">গ্রাহক এই কোডটাই কার্টে লিখবে। ছোট-বড় হাতের অক্ষরে পার্থক্য নেই।</div>
                            </div>
                            <div class="col-md-7">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label fw-bold d-flex align-items-center">
                                            <img src="https://flagcdn.com/w20/gb.png" class="me-2" alt="EN"> Label (English)
                                        </label>
                                        <input type="text" name="label_en" class="form-control @error('label_en') is-invalid @enderror"
                                               value="{{ old('label_en', $coupon->label_en ?? '') }}" placeholder="Eid Special">
                                        @error('label_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold d-flex align-items-center">
                                            <img src="https://flagcdn.com/w20/bd.png" class="me-2" alt="BN"> লেবেল (বাংলা)
                                        </label>
                                        <input type="text" name="label_bn" class="form-control @error('label_bn') is-invalid @enderror"
                                               value="{{ old('label_bn', $coupon->label_bn ?? '') }}" placeholder="ঈদ অফার">
                                        @error('label_bn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="form-text mt-2">কার্টে কোডের পাশে এই নামটা দেখাবে। খালি রাখলে কোডটাই দেখাবে।</div>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light border-0 p-3 mt-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-percent"></i> ছাড়</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Type *</label>
                                <select name="type" id="coupon-type" class="form-select @error('type') is-invalid @enderror">
                                    @foreach(\App\Models\Coupon::TYPES as $key => $label)
                                        <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Value *</label>
                                <input type="number" name="value" step="0.01" min="0.01"
                                       class="form-control @error('value') is-invalid @enderror"
                                       value="{{ old('value', $coupon->value ?? '') }}" required>
                                @error('value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text" data-when="percent">১০০-এর বেশি দিলে ১০০ ধরা হবে।</div>
                                <div class="form-text" data-when="fixed">প্রতি ইউনিটে এই টাকাটা ছাড় যাবে।</div>
                            </div>
                            <div class="col-md-4" data-when="percent">
                                <label class="form-label fw-bold">Max discount (৳)</label>
                                <input type="number" name="max_discount" step="0.01" min="0"
                                       class="form-control @error('max_discount') is-invalid @enderror"
                                       value="{{ old('max_discount', $coupon->max_discount ?? '') }}" placeholder="300">
                                @error('max_discount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">প্রতি ইউনিটে ছাড়ের সর্বোচ্চ সীমা। খালি = সীমা নেই।</div>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-3 mb-0 small">
                            <i class="bi bi-info-circle"></i>
                            <strong>নিয়ম:</strong> কোনো পণ্যে আগে থেকেই অফার থাকলে দুটো ছাড় যোগ হবে না।
                            প্রতি ইউনিটে হিসাব করে <strong>যেটার ছাড় বেশি সেটাই</strong> বসবে, অন্যটা বাদ যাবে।
                        </div>
                    </div>

                    <div class="card bg-light border-0 p-3 mt-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-bullseye"></i> কোন পণ্যে খাটবে</h6>
                        <select name="applies_to" id="coupon-scope" class="form-select @error('applies_to') is-invalid @enderror">
                            @foreach(\App\Models\Coupon::SCOPES as $key => $label)
                                <option value="{{ $key }}" @selected($scope === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('applies_to') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <div class="mt-3" data-scope="categories">
                            <label class="form-label fw-bold">Categories</label>
                            <div class="border rounded bg-white p-3" style="max-height: 260px; overflow-y: auto;">
                                @forelse($categories as $category)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="category_ids[]"
                                               value="{{ $category->id }}" id="cat-{{ $category->id }}"
                                               @checked($pickedCategories->contains($category->id))>
                                        <label class="form-check-label" for="cat-{{ $category->id }}">{{ $category->name }}</label>
                                    </div>
                                @empty
                                    <p class="text-muted small mb-0">কোনো সক্রিয় ক্যাটাগরি নেই।</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="mt-3" data-scope="products">
                            <label class="form-label fw-bold">Products</label>
                            <input type="search" class="form-control mb-2" id="product-filter" placeholder="নাম দিয়ে খুঁজুন…">
                            <div class="border rounded bg-white p-3" style="max-height: 300px; overflow-y: auto;" id="product-list">
                                @forelse($products as $product)
                                    <div class="form-check" data-name="{{ Str::lower($product->name) }}">
                                        <input class="form-check-input" type="checkbox" name="product_ids[]"
                                               value="{{ $product->id }}" id="prod-{{ $product->id }}"
                                               @checked($pickedProducts->contains($product->id))>
                                        <label class="form-check-label" for="prod-{{ $product->id }}">
                                            {{ $product->name }}
                                            <span class="text-muted small">— {{ $product->category->name ?? '—' }}</span>
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-muted small mb-0">কোনো সক্রিয় পণ্য নেই।</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Minimum order (৳)</label>
                        <input type="number" name="min_order_amount" step="0.01" min="0"
                               class="form-control @error('min_order_amount') is-invalid @enderror"
                               value="{{ old('min_order_amount', $coupon->min_order_amount ?? '') }}">
                        @error('min_order_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">এর নিচের অর্ডারে কোডটা নেবে না।</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Starts</label>
                            <input type="datetime-local" name="starts_at"
                                   class="form-control @error('starts_at') is-invalid @enderror"
                                   value="{{ old('starts_at', isset($coupon) ? $coupon->starts_at?->format('Y-m-d\TH:i') : '') }}">
                            @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Ends</label>
                            <input type="datetime-local" name="ends_at"
                                   class="form-control @error('ends_at') is-invalid @enderror"
                                   value="{{ old('ends_at', isset($coupon) ? $coupon->ends_at?->format('Y-m-d\TH:i') : '') }}">
                            @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12"><div class="form-text">দুটোই খালি রাখলে কোডটা সবসময় চলবে।</div></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Total uses</label>
                        <input type="number" name="usage_limit" min="1"
                               class="form-control @error('usage_limit') is-invalid @enderror"
                               value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}">
                        @error('usage_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">
                            খালি = সীমাহীন।
                            @isset($coupon) এ পর্যন্ত ব্যবহার: <strong>{{ $coupon->used_count }}</strong>। @endisset
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Uses per customer</label>
                        <input type="number" name="usage_limit_per_user" min="1"
                               class="form-control @error('usage_limit_per_user') is-invalid @enderror"
                               value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user ?? '') }}">
                        @error('usage_limit_per_user') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">লগইন করা গ্রাহকের ক্ষেত্রেই কেবল গোনা যায়।</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', $coupon->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 mt-3">
                        <i class="bi bi-check-circle"></i> {{ isset($coupon) ? 'Update Coupon' : 'Create Coupon' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Show only the fields the chosen type and scope actually use. The server
    // clears the rest regardless, so a hidden box cannot reach the database.
    (function () {
        const form = document.getElementById('coupon-form');
        const type = document.getElementById('coupon-type');
        const scope = document.getElementById('coupon-scope');

        function sync() {
            form.querySelectorAll('[data-when]').forEach((el) => {
                el.hidden = el.dataset.when !== type.value;
            });
            form.querySelectorAll('[data-scope]').forEach((el) => {
                el.hidden = el.dataset.scope !== scope.value;
            });
        }

        type.addEventListener('change', sync);
        scope.addEventListener('change', sync);
        sync();

        // The product list can run to hundreds of rows; let the admin narrow it.
        const filter = document.getElementById('product-filter');
        const list = document.getElementById('product-list');

        filter?.addEventListener('input', () => {
            const needle = filter.value.trim().toLowerCase();

            list.querySelectorAll('[data-name]').forEach((row) => {
                row.hidden = needle !== '' && !row.dataset.name.includes(needle);
            });
        });
    })();
</script>
@endpush
@endsection
