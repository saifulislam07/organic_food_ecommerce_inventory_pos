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
                <input type="hidden" name="site_title[type]" value="text">
            </div>
            <div class="col-md-6">
                <label class="form-label">Site Title (Bengali)</label>
                <input type="text" name="site_title[value_bn]" class="form-control" value="{{ \App\Models\Setting::where('key', 'site_title')->first()->value_bn ?? 'ম্যাঙ্গো হাট' }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Site Logo</label>
                <input type="file" name="logo[value_en]" class="form-control">
                <input type="hidden" name="logo[type]" value="image">
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
                <input type="hidden" name="hero_title[type]" value="text">
            </div>
            <div class="col-md-6">
                <label class="form-label">Hero Title (Bengali) <small>HTML allowed</small></label>
                <input type="text" name="hero_title[value_bn]" class="form-control" value="{{ \App\Models\Setting::get('hero_title', '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Hero Description (English)</label>
                <textarea name="hero_desc[value_en]" class="form-control" rows="2">{{ \App\Models\Setting::get('hero_desc', '') }}</textarea>
                <input type="hidden" name="hero_desc[type]" value="textarea">
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
                <input type="hidden" name="whatsapp[type]" value="text">
            </div>

            <div class="col-md-6">
                <label class="form-label">Call Support Number</label>
                <input type="text" name="phone[value_en]" class="form-control" value="{{ \App\Models\Setting::where('key', 'phone')->first()->value_en ?? '' }}">
                <input type="hidden" name="phone[value_bn]" value="{{ \App\Models\Setting::where('key', 'phone')->first()->value_en ?? '' }}">
                <input type="hidden" name="phone[type]" value="text">
            </div>

            <div class="col-md-12">
                <label class="form-label">Address (English)</label>
                <textarea name="address[value_en]" class="form-control" rows="2">{{ \App\Models\Setting::where('key', 'address')->first()->value_en ?? '' }}</textarea>
                <input type="hidden" name="address[type]" value="textarea">
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
                <input type="hidden" name="facebook[type]" value="text">
            </div>

            <div class="col-md-6">
                <label class="form-label">YouTube URL</label>
                <input type="text" name="youtube[value_en]" class="form-control" value="{{ \App\Models\Setting::where('key', 'youtube')->first()->value_en ?? '' }}">
                <input type="hidden" name="youtube[value_bn]" value="{{ \App\Models\Setting::where('key', 'youtube')->first()->value_en ?? '' }}">
                <input type="hidden" name="youtube[type]" value="text">
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
                <input type="hidden" name="shipping_fee_inside[type]" value="number">
                <div class="form-text">Delivery charge for addresses inside Dhaka city.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Outside Dhaka Shipping Fee (৳)</label>
                <input type="number" name="shipping_fee_outside[value_en]" class="form-control" value="{{ \App\Models\Setting::get('shipping_fee_outside', 120) }}">
                <input type="hidden" name="shipping_fee_outside[value_bn]" value="{{ \App\Models\Setting::get('shipping_fee_outside', 120) }}">
                <input type="hidden" name="shipping_fee_outside[type]" value="number">
                <div class="form-text">Delivery charge for addresses outside Dhaka city.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Free Delivery Threshold (৳)</label>
                <input type="number" name="free_delivery_threshold[value_en]" class="form-control" value="{{ \App\Models\Setting::get('free_delivery_threshold', 2000) }}">
                <input type="hidden" name="free_delivery_threshold[value_bn]" value="{{ \App\Models\Setting::get('free_delivery_threshold', 2000) }}">
                <input type="hidden" name="free_delivery_threshold[type]" value="number">
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
