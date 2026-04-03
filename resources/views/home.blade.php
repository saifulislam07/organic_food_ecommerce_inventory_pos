@extends('layouts.frontend')

@push('styles')
<style>
    /* Safe Mobile-Only Optimizations */
    @media (max-width: 991px) {
        .hero-section { padding: 40px 0; text-align: center; }
        .hero-content { display: flex; flex-direction: column; align-items: center; }
        .hero-desc { margin-left: auto; margin-right: auto; }
        .hero-stats { justify-content: center; gap: 15px; margin-top: 30px; flex-wrap: wrap; }
        .hero-btn { width: 100%; justify-content: center; }
        .btn-whatsapp { width: 100%; justify-content: center; }
    }
</style>
@endpush

@section('title', 'Mango Hut – খাঁটি ও অর্গানিক পণ্যের অনলাইন বাজার')

@section('content')
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <div class="hero-badge">
                            <i class="bi bi-patch-check-fill"></i> {{ app()->getLocale() == 'bn' ? '১০০% খাঁটি ও অর্গানিক' : '100% Pure & Organic' }}
                        </div>
                        <h1 class="hero-title">
                            {!! \App\Models\Setting::get('hero_title', 'Pure & Organic <br><span>Nature</span> Online Market') !!}
                        </h1>
                        <p class="hero-desc">
                            {{ \App\Models\Setting::get('hero_desc', 'Directly from Chapainawabganj to your doorstep.') }}
                        </p>
                        <a href="{{ route('shop') }}" class="hero-btn">
                            <i class="bi bi-shop"></i> {{ app()->getLocale() == 'bn' ? 'শপ করুন' : 'Shop Now' }}
                        </a>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <div class="hero-stat-num">500+</div>
                                <div class="hero-stat-label">{{ app()->getLocale() == 'bn' ? 'সন্তুষ্ট গ্রাহক' : 'Happy Customers' }}</div>
                            </div>
                            <div class="hero-stat">
                                <div class="hero-stat-num">50+</div>
                                <div class="hero-stat-label">{{ app()->getLocale() == 'bn' ? 'পণ্যসমূহ' : 'Products' }}</div>
                            </div>
                            <div class="hero-stat">
                                <div class="hero-stat-num">100%</div>
                                <div class="hero-stat-label">{{ app()->getLocale() == 'bn' ? 'অর্গানিক' : 'Organic' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="hero-image-wrapper text-center">
                        <img src="{{ asset('images/hero-mango.png') }}" alt="Premium Mango Basket" class="img-fluid rounded-4 shadow-lg hero-floating-img" style="max-height: 450px; border: 8px solid white;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Best Selling Products -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge"><i class="bi bi-star-fill"></i> {{ app()->getLocale() == 'bn' ? 'সেরা পণ্য' : 'Best Sellers' }}</div>
                <h2 class="section-title">{{ app()->getLocale() == 'bn' ? 'জনপ্রিয় পণ্যসমূহ' : 'Popular Products' }}</h2>
                <p class="section-subtitle">{{ app()->getLocale() == 'bn' ? 'আমাদের সবচেয়ে বিক্রিত প্রাকৃতিক ও অর্গানিক পণ্য' : 'Our most sold natural & organic products' }}</p>
            </div>
            <div class="row g-4">
                @foreach($bestSellers as $product)
                    <div class="col-xl-3 col-lg-4 col-md-6 col-6">
                        @include('partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Category Promo Section -->
    <section class="section section-alt">
        <div class="container">
            <div class="section-header">
                <div class="section-badge"><i class="bi bi-collection"></i> {{ app()->getLocale() == 'bn' ? 'ক্যাটাগরি' : 'Categories' }}</div>
                <h2 class="section-title">{{ app()->getLocale() == 'bn' ? 'আমাদের পণ্য ক্যাটাগরি' : 'Our Product Categories' }}</h2>
            </div>
            <div class="row g-4">
                @foreach($categories as $category)
                <div class="col-lg-4 col-md-6">
                    <div class="category-promo" style="background-image: url('{{ $category->image_url }}');">
                        <div class="category-promo-content">
                            <h3>{{ $category->name }}</h3>
                            <p>{{ $category->description ?? '' }}</p>
                            <a href="{{ route('shop', ['category' => $category->slug]) }}" class="promo-btn">
                                {{ app()->getLocale() == 'bn' ? 'বিস্তারিত দেখুন' : 'Shop Now' }} <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Trending Products -->
    @if($trending->count())
    <section class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge"><i class="bi bi-fire"></i> {{ app()->getLocale() == 'bn' ? 'ট্রেন্ডিং' : 'Trending' }}</div>
                <h2 class="section-title">{{ app()->getLocale() == 'bn' ? 'ট্রেন্ডিং পণ্যসমূহ' : 'Trending Products' }}</h2>
                <p class="section-subtitle">{{ app()->getLocale() == 'bn' ? 'এই মুহূর্তে সবচেয়ে জনপ্রিয়' : 'Most popular right now' }}</p>
            </div>
            <div class="row g-4">
                @foreach($trending as $product)
                    <div class="col-xl-3 col-lg-4 col-md-6 col-6">
                        @include('partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
            <div class="text-center mt-4">
                <a href="{{ route('shop') }}" class="hero-btn" style="font-size: 1rem; padding: 12px 30px;">
                    {{ app()->getLocale() == 'bn' ? 'সব পণ্য দেখুন' : 'View All Products' }} <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
    @endif

    <!-- CTA Section -->
    <section class="section section-alt">
        <div class="container text-center">
            <div class="section-badge"><i class="bi bi-whatsapp"></i> {{ app()->getLocale() == 'bn' ? 'সহজ অর্ডার' : 'Easy Order' }}</div>
            <h2 class="section-title">{{ app()->getLocale() == 'bn' ? 'সহজেই অর্ডার করুন' : 'Order Effortlessly' }}</h2>
            <p class="section-subtitle mb-4">{{ app()->getLocale() == 'bn' ? 'ফোন কল, WhatsApp বা সরাসরি ওয়েবসাইট থেকে অর্ডার করুন' : 'Order via phone, WhatsApp, or directly from our website' }}</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="https://wa.me/{{ \App\Models\Setting::get('whatsapp', '8801716952365') }}?text={{ app()->getLocale() == 'bn' ? 'হ্যালো! আমি অর্ডার করতে চাই।' : 'Hello! I want to order.' }}" target="_blank" class="btn-whatsapp">
                    <i class="bi bi-whatsapp"></i> {{ app()->getLocale() == 'bn' ? 'WhatsApp এ অর্ডার করুন' : 'Order on WhatsApp' }}
                </a>
                <a href="{{ route('shop') }}" class="btn-primary-custom">
                    <i class="bi bi-shop"></i> {{ app()->getLocale() == 'bn' ? 'শপ করুন' : 'Shop Now' }}
                </a>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
// Scroll animation
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));
</script>
@endpush
