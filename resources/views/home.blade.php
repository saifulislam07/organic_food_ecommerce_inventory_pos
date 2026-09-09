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

@section('title', 'MohiPure – খাঁটি ও অর্গানিক পণ্যের অনলাইন বাজার')

@section('content')
    {{--
        Hero Section — one carousel panel per hero_slides row. A shop with no
        slides gets a single panel built from the site settings, so the markup
        below has only one path through it either way.
    --}}
    <section class="hero-section">
        <div id="heroSlider" class="carousel slide hero-carousel w-100"
             data-bs-ride="carousel" data-bs-interval="6000" data-bs-pause="hover">
            <div class="carousel-inner">
                @foreach($slides as $slide)
                <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-lg-6">
                                <div class="hero-content">
                                    @if($slide->badge)
                                    <div class="hero-badge">
                                        <i class="bi bi-patch-check-fill"></i> {{ $slide->badge }}
                                    </div>
                                    @endif
                                    {{-- Admin-authored, and allowed the <br> and <span> the styling needs. --}}
                                    <h1 class="hero-title">{!! $slide->title !!}</h1>
                                    @if($slide->subtitle)
                                    <p class="hero-desc">{{ $slide->subtitle }}</p>
                                    @endif
                                    <a href="{{ $slide->button_link }}" class="hero-btn">
                                        <i class="bi bi-shop"></i> {{ $slide->button_text }}
                                    </a>
                                    @include('partials.hero-stats')
                                </div>
                            </div>
                            <div class="col-lg-6 d-none d-lg-block">
                                <div class="hero-image-wrapper text-center">
                                    <img src="{{ $slide->image_url }}" alt="{{ strip_tags($slide->title) }}"
                                         class="img-fluid rounded-4 shadow-lg hero-floating-img"
                                         loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                         style="max-height: 450px; border: 8px solid white;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- A single slide is not a slider: no arrows, no dots, nothing to press. --}}
            @if($slides->count() > 1)
            <button class="carousel-control-prev hero-slider-arrow" type="button"
                    data-bs-target="#heroSlider" data-bs-slide="prev">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                <span class="visually-hidden">{{ app()->getLocale() == 'bn' ? 'আগেরটি' : 'Previous' }}</span>
            </button>
            <button class="carousel-control-next hero-slider-arrow" type="button"
                    data-bs-target="#heroSlider" data-bs-slide="next">
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                <span class="visually-hidden">{{ app()->getLocale() == 'bn' ? 'পরেরটি' : 'Next' }}</span>
            </button>

            <div class="carousel-indicators hero-slider-dots">
                @foreach($slides as $slide)
                <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="{{ $loop->index }}"
                        class="{{ $loop->first ? 'active' : '' }}"
                        @if($loop->first) aria-current="true" @endif
                        aria-label="{{ (app()->getLocale() == 'bn' ? 'স্লাইড ' : 'Slide ').$loop->iteration }}"></button>
                @endforeach
            </div>
            @endif
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

    <!-- Combo Offers -->
    @if($combos->count())
    <section class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-badge"><i class="bi bi-box2-fill"></i> {{ app()->getLocale() == 'bn' ? 'কম্বো' : 'Combo' }}</div>
                <h2 class="section-title">{{ app()->getLocale() == 'bn' ? 'কম্বো অফার' : 'Combo Offers' }}</h2>
                <p class="section-subtitle">{{ app()->getLocale() == 'bn' ? 'একসাথে কিনলে দাম কম' : 'Buy them together and pay less' }}</p>
            </div>
            <div class="row g-4">
                @foreach($combos as $product)
                    <div class="col-xl-3 col-lg-4 col-md-6 col-6">
                        @include('partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

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
                @if($orderUrl = \App\Support\Whatsapp::shopUrl(app()->getLocale() == 'bn' ? 'হ্যালো! আমি অর্ডার করতে চাই।' : 'Hello! I want to order.'))
                <a href="{{ $orderUrl }}" target="_blank" rel="noopener" class="btn-whatsapp">
                    <i class="bi bi-whatsapp"></i> {{ app()->getLocale() == 'bn' ? 'WhatsApp এ অর্ডার করুন' : 'Order on WhatsApp' }}
                </a>
                @endif
                <a href="{{ route('shop') }}" class="btn-primary-custom">
                    <i class="bi bi-shop"></i> {{ app()->getLocale() == 'bn' ? 'শপ করুন' : 'Shop Now' }}
                </a>
            </div>
        </div>
    </section>
@endsection
