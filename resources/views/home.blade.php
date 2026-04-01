@extends('layouts.frontend')

@push('styles')
<style>
    /* Hero Section Responsiveness */
    .hero-section {
        padding: 100px 0 60px;
        background: linear-gradient(135deg, #f8fdfb 0%, #e8f5e9 100%);
        position: relative;
        overflow: hidden;
    }
    .hero-content { position: relative; z-index: 2; }
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(45, 106, 79, 0.1);
        color: var(--primary);
        padding: 6px 16px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 20px;
    }
    .hero-title {
        margin-bottom: 25px;
    }
    .hero-title span { color: var(--primary); }
    .hero-desc {
        font-size: 1.1rem;
        color: #555;
        margin-bottom: 35px;
        max-width: 500px;
    }
    .hero-btn {
        background: var(--primary);
        color: white;
        padding: 14px 35px;
        border-radius: var(--radius-md);
        text-decoration: none;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: var(--transition);
        box-shadow: 0 10px 20px rgba(45, 106, 79, 0.2);
    }
    .hero-btn:hover {
        background: var(--primary-light);
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(45, 106, 79, 0.3);
        color: white;
    }

    .hero-stats {
        display: flex;
        gap: 30px;
        margin-top: 50px;
    }
    .hero-stat-num {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--primary);
        line-height: 1;
    }
    .hero-stat-label {
        font-size: 0.85rem;
        color: var(--gray-500);
        font-weight: 500;
    }

    @media (max-width: 991px) {
        .hero-section { text-align: center; padding: 60px 0 40px; }
        .hero-content { display: flex; flex-direction: column; align-items: center; }
        .hero-desc { margin-left: auto; margin-right: auto; }
        .hero-stats { justify-content: center; gap: 20px; }
    }

    /* Section Headers */
    .section-header { text-align: center; margin-bottom: 50px; }
    .section-badge {
        display: inline-block;
        color: var(--secondary);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 0.8rem;
        margin-bottom: 10px;
    }
    .section-title { margin-bottom: 15px; }
    .section-subtitle { color: var(--gray-500); max-width: 600px; margin: 0 auto; }

    /* Category Promo */
    .category-promo {
        height: 280px;
        border-radius: var(--radius-lg);
        background-size: cover;
        background-position: center;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        padding: 30px;
        transition: var(--transition);
        z-index: 1;
    }
    .category-promo::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 60%);
        z-index: -1;
    }
    .category-promo:hover { transform: translateY(-5px); }
    .category-promo-content h3 { color: white; margin-bottom: 8px; }
    .category-promo-content p { color: rgba(255,255,255,0.8); font-size: 0.9rem; margin-bottom: 15px; }
    .promo-btn {
        color: var(--secondary);
        text-decoration: none;
        font-weight: 700;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    /* CTA Section Layout */
    .btn-whatsapp {
        background: #25d366;
        color: white;
        padding: 12px 30px;
        border-radius: var(--radius-md);
        text-decoration: none;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: var(--transition);
    }
    .btn-whatsapp:hover { background: #128c7e; transform: translateY(-3px); color: white; }
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
                            <i class="bi bi-patch-check-fill"></i> ১০০% খাঁটি ও অর্গানিক
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
                    <div class="hero-image-wrapper">
                        <div style="font-size: 12rem; text-align: center; filter: drop-shadow(0 20px 40px rgba(0,0,0,0.3));">
                            🥭
                        </div>
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
