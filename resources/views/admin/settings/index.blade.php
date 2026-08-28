@extends('admin.layouts.app')
@section('page_title', 'Site Settings')

@section('content')
<div class="card admin-card p-4">
    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="row g-4">
            <!-- Branding Section -->
            <div class="col-12">
                <h5 class="fw-bold border-bottom pb-2 mb-3" style="color: var(--primary-dark);">
                    <i class="bi bi-megaphone"></i> Branding & Identity
                </h5>
            </div>

            <div class="col-md-6">
                <label class="form-label">Site Title (English)</label>
                <input type="text" name="site_title[value_en]" class="form-control" value="{{ \App\Models\Setting::where('key', 'site_title')->first()->value_en ?? 'Mango Hut' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Site Title (Bengali)</label>
                <input type="text" name="site_title[value_bn]" class="form-control" value="{{ \App\Models\Setting::where('key', 'site_title')->first()->value_bn ?? 'ম্যাঙ্গো হাট' }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Site Logo</label>
                <input type="file" name="logo[value_en]" class="form-control">
                @if($logo = \App\Models\Setting::where('key', 'logo')->first())
                    <div class="mt-2 text-center p-2 border rounded bg-light" style="max-width: 150px;">
                        <img src="{{ asset('storage/' . $logo->value_en) }}" alt="Logo" class="img-fluid" style="max-height: 50px;">
                    </div>
                @endif
            </div>

            <!-- Hero Section -->
            <div class="col-12 mt-5">
                <h5 class="fw-bold border-bottom pb-2 mb-3" style="color: var(--primary-dark);">
                    <i class="bi bi-star"></i> Homepage Hero Section
                </h5>
            </div>

            <div class="col-md-6">
                <label class="form-label">Hero Title (English) <small>HTML allowed</small></label>
                <input type="text" name="hero_title[value_en]" class="form-control" value="{{ \App\Models\Setting::get('hero_title', '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Hero Title (Bengali) <small>HTML allowed</small></label>
                <input type="text" name="hero_title[value_bn]" class="form-control" value="{{ \App\Models\Setting::get('hero_title', '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Hero Description (English)</label>
                <textarea name="hero_desc[value_en]" class="form-control" rows="2">{{ \App\Models\Setting::get('hero_desc', '') }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Hero Description (Bengali)</label>
                <textarea name="hero_desc[value_bn]" class="form-control" rows="2">{{ \App\Models\Setting::get('hero_desc', '') }}</textarea>
            </div>

            <!-- Contact Section -->
            <div class="col-12 mt-5">
                <h5 class="fw-bold border-bottom pb-2 mb-3" style="color: var(--primary-dark);">
                    <i class="bi bi-telephone"></i> Contact Information
                </h5>
            </div>

            <div class="col-md-6">
                <label class="form-label">WhatsApp Number</label>
                <input type="text" name="whatsapp[value_en]" class="form-control" value="{{ \App\Models\Setting::where('key', 'whatsapp')->first()->value_en ?? '' }}">
                <input type="hidden" name="whatsapp[value_bn]" value="{{ \App\Models\Setting::where('key', 'whatsapp')->first()->value_en ?? '' }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Call Support Number</label>
                <input type="text" name="phone[value_en]" class="form-control" value="{{ \App\Models\Setting::where('key', 'phone')->first()->value_en ?? '' }}">
                <input type="hidden" name="phone[value_bn]" value="{{ \App\Models\Setting::where('key', 'phone')->first()->value_en ?? '' }}">
            </div>

            <div class="col-md-12">
                <label class="form-label">Address (English)</label>
                <textarea name="address[value_en]" class="form-control" rows="2">{{ \App\Models\Setting::where('key', 'address')->first()->value_en ?? '' }}</textarea>
            </div>
            <div class="col-md-12">
                <label class="form-label">Address (Bengali)</label>
                <textarea name="address[value_bn]" class="form-control" rows="2">{{ \App\Models\Setting::where('key', 'address')->first()->value_bn ?? '' }}</textarea>
            </div>

            <!-- Footer Section -->
            <div class="col-12 mt-5">
                <h5 class="fw-bold border-bottom pb-2 mb-3" style="color: var(--primary-dark);">
                    <i class="bi bi-layout-text-sidebar"></i> Footer & Socials
                </h5>
            </div>

            <div class="col-md-6">
                <label class="form-label">Facebook URL</label>
                <input type="text" name="facebook[value_en]" class="form-control" value="{{ \App\Models\Setting::where('key', 'facebook')->first()->value_en ?? '' }}">
                <input type="hidden" name="facebook[value_bn]" value="{{ \App\Models\Setting::where('key', 'facebook')->first()->value_en ?? '' }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">YouTube URL</label>
                <input type="text" name="youtube[value_en]" class="form-control" value="{{ \App\Models\Setting::where('key', 'youtube')->first()->value_en ?? '' }}">
                <input type="hidden" name="youtube[value_bn]" value="{{ \App\Models\Setting::where('key', 'youtube')->first()->value_en ?? '' }}">
            </div>

            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-instagram me-1"></i> Instagram URL</label>
                <input type="text" name="instagram[value_en]" class="form-control @error('instagram.value_en') is-invalid @enderror"
                       value="{{ \App\Models\Setting::where('key', 'instagram')->first()->value_en ?? '' }}"
                       placeholder="https://www.instagram.com/yourshop">
                <input type="hidden" name="instagram[value_bn]" value="{{ \App\Models\Setting::where('key', 'instagram')->first()->value_en ?? '' }}">
                @error('instagram.value_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label"><i class="bi bi-tiktok me-1"></i> TikTok URL</label>
                <input type="text" name="tiktok[value_en]" class="form-control @error('tiktok.value_en') is-invalid @enderror"
                       value="{{ \App\Models\Setting::where('key', 'tiktok')->first()->value_en ?? '' }}"
                       placeholder="https://www.tiktok.com/@yourshop">
                <input type="hidden" name="tiktok[value_bn]" value="{{ \App\Models\Setting::where('key', 'tiktok')->first()->value_en ?? '' }}">
                @error('tiktok.value_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <div class="alert alert-light border small mb-0">
                    <i class="bi bi-info-circle"></i>
                    A blank field simply hides that icon in the footer. WhatsApp uses the
                    number from the Contact section above.
                </div>
            </div>

            <!-- Shipping Section -->
            <div class="col-12 mt-5">
                <h5 class="fw-bold border-bottom pb-2 mb-3" style="color: var(--primary-dark);">
                    <i class="bi bi-truck"></i> Shipping & Delivery
                </h5>
            </div>

            <div class="col-md-6">
                <label class="form-label">Inside Dhaka Shipping Fee (৳)</label>
                <input type="number" name="shipping_fee_inside[value_en]" class="form-control" value="{{ \App\Models\Setting::get('shipping_fee_inside', 60) }}">
                <input type="hidden" name="shipping_fee_inside[value_bn]" value="{{ \App\Models\Setting::get('shipping_fee_inside', 60) }}">
                <div class="form-text">Delivery charge for addresses inside Dhaka city.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Outside Dhaka Shipping Fee (৳)</label>
                <input type="number" name="shipping_fee_outside[value_en]" class="form-control" value="{{ \App\Models\Setting::get('shipping_fee_outside', 120) }}">
                <input type="hidden" name="shipping_fee_outside[value_bn]" value="{{ \App\Models\Setting::get('shipping_fee_outside', 120) }}">
                <div class="form-text">Delivery charge for addresses outside Dhaka city.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Free Delivery Threshold (৳)</label>
                <input type="number" name="free_delivery_threshold[value_en]" class="form-control" value="{{ \App\Models\Setting::get('free_delivery_threshold', 2000) }}">
                <input type="hidden" name="free_delivery_threshold[value_bn]" value="{{ \App\Models\Setting::get('free_delivery_threshold', 2000) }}">
                <div class="form-text">Orders above this amount will have 0 delivery charge.</div>
            </div>

            <div class="col-12 mt-4">
                <button type="submit" class="btn btn-primary px-5">
                    <i class="bi bi-save"></i> Save All Settings
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
