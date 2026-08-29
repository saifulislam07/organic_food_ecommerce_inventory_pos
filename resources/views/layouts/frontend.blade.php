<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('partials.favicon')

    @php
        $seoTitle = trim($__env->yieldContent('title', \App\Support\SeoSettings::get('seo_meta_title')
            ?: \App\Models\Setting::get('site_title', 'MohiPure')));
        $seoDescription = trim($__env->yieldContent('meta_description', \App\Support\SeoSettings::get('seo_meta_description') ?: ''));
        $seoKeywords = \App\Support\SeoSettings::get('seo_meta_keywords');
        $seoImage = trim($__env->yieldContent('og_image', \App\Support\SeoSettings::ogImageUrl() ?: ''));
        $seoVerification = \App\Support\SeoSettings::get('seo_google_site_verification');
        $analyticsId = \App\Support\SeoSettings::analyticsId();
    @endphp

    <title>{{ $seoTitle }}</title>
    @if($seoDescription)
        <meta name="description" content="{{ $seoDescription }}">
    @endif
    @if($seoKeywords)
        <meta name="keywords" content="{{ $seoKeywords }}">
    @endif
    <meta name="robots" content="{{ \App\Support\SeoSettings::robots() }}">
    <link rel="canonical" href="{{ url()->current() }}">
    @if($seoVerification)
        <meta name="google-site-verification" content="{{ $seoVerification }}">
    @endif

    {{-- Open Graph: what Facebook, WhatsApp and Messenger show when a link is shared --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ \App\Models\Setting::get('site_title', 'MohiPure') }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'bn' ? 'bn_BD' : 'en_US' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    @if($seoDescription)
        <meta property="og:description" content="{{ $seoDescription }}">
    @endif
    @if($seoImage)
        <meta property="og:image" content="{{ $seoImage }}">
    @endif

    <meta name="twitter:card" content="{{ $seoImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    @if($seoDescription)
        <meta name="twitter:description" content="{{ $seoDescription }}">
    @endif
    @if($seoImage)
        <meta name="twitter:image" content="{{ $seoImage }}">
    @endif

    @if($analyticsId)
        {{-- Only loaded once a measurement ID is saved in SEO settings --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analyticsId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($analyticsId));
        </script>
    @endif

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="{{ asset('css/brand.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    {{-- Runtime config for resources/js/storefront.js --}}
    @php
        $storefrontConfig = [
            'routes' => [
                'add' => route('cart.add'),
                'update' => route('cart.update'),
                'remove' => route('cart.remove'),
                'count' => route('cart.count'),
                'mini' => route('cart.mini'),
            ],
            'locale' => app()->getLocale(),
            'freeDeliveryThreshold' => (float) \App\Models\Setting::get('free_delivery_threshold', 2000),
            'whatsapp' => \App\Models\Setting::get('whatsapp', '8801716952365'),
            'strings' => [
                'added' => app()->getLocale() == 'bn' ? 'কার্টে যোগ করা হয়েছে!' : 'Added to cart!',
                'removed' => app()->getLocale() == 'bn' ? 'পণ্যটি সরানো হয়েছে' : 'Item removed',
                'error' => app()->getLocale() == 'bn' ? 'কিছু ভুল হয়েছে!' : 'Something went wrong!',
            ],
        ];
    @endphp
    <script type="application/json" id="storefront-config">{!! json_encode($storefrontConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>

    @vite(['resources/js/storefront.js'])
    <style>
        /* The brand palette lives in css/brand.css. Only the extra tokens this
           layout needs on top of it are declared here — redeclaring the brand
           colours would silently override the theme, since this block loads
           after the stylesheet. */
        :root {
            --secondary: var(--accent);
            --light: #f8f9fa;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --radius-md: 12px;
        }

        body {
            font-family: 'Hind Siliguri', sans-serif;
            color: var(--dark);
            background-color: var(--cream);
            overflow-x: hidden;
            font-size: 0.95rem;
        }

        /* Responsive Headings */
        h1 { font-size: clamp(1.8rem, 5vw, 2.8rem); font-weight: 700; color: var(--dark); line-height: 1.2; }
        h2 { font-size: clamp(1.5rem, 4vw, 2.2rem); font-weight: 700; color: var(--dark); }
        h3 { font-size: clamp(1.2rem, 3vw, 1.6rem); font-weight: 600; color: var(--dark); }

        .section { padding: 60px 0; }
        .section-alt { background-color: var(--gray-100); }
        
        @media (max-width: 768px) {
            .section { padding: 40px 0; }
        }

        /* Navbar & Nav Links */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-sm);
            padding: 12px 0;
            transition: var(--transition);
        }
        .nav-link {
            color: var(--dark) !important;
            font-weight: 500;
            padding: 8px 15px !important;
            transition: var(--transition);
        }
        .nav-link:hover, .nav-link.active {
            color: var(--primary) !important;
        }

        /* Top Bar Optimization */
        .top-bar {
            background: var(--primary-dark);
            color: white;
            padding: 8px 0;
            font-size: 0.8rem;
            z-index: 1090;
            position: relative;
        }
        @media (max-width: 576px) {
            .top-bar-text { font-size: 0.7rem; text-align: center; }
        }

        /* Robust Language Dropdown CSS */
        .custom-lang-dropdown { position: relative; z-index: 1100; }
        .lang-trigger {
            background: transparent; border: none; color: white;
            display: flex; align-items: center; gap: 5px; font-size: 0.8rem;
            cursor: pointer; padding: 4px; border-radius: 4px; transition: var(--transition);
        }
        .lang-trigger:hover, .lang-trigger:focus-within { background: rgba(255,255,255,0.1); }
        .lang-menu {
            position: absolute; top: 100%; left: 50%; transform: translateX(-50%);
            background: var(--primary-dark);
            border: 1px solid rgba(255,255,255,0.1); min-width: 100px;
            border-radius: var(--radius-sm); z-index: 1000;
            display: none; flex-direction: column; overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .custom-lang-dropdown:hover .lang-menu { display: flex; }
        .lang-menu a {
            color: rgba(255,255,255,0.8); text-decoration: none;
            padding: 8px 15px; font-size: 0.8rem; transition: var(--transition); text-align: center;
        }
        .lang-menu a:hover, .lang-menu a.active {
            background: rgba(255,255,255,0.1); color: white;
        }
        .top-bar-link { color: rgba(255,255,255,0.9); text-decoration: none; transition: var(--transition); display: flex; align-items: center; gap: 6px; }
        .top-bar-link:hover { color: var(--secondary); }


        /* Buttons */
        .btn-primary-custom {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: var(--radius-md);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            text-decoration: none;
        }
        .btn-primary-custom:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        /* Mobile Search Bar */
        .mobile-search-form { margin-bottom: 15px; }
        .mobile-search-form .form-control {
            border-radius: var(--radius-md) 0 0 var(--radius-md);
            border-color: var(--gray-200);
            padding: 12px 18px;
        }
        .mobile-search-form .btn {
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
            padding: 0 20px;
        }

        /* Mobile Bottom Nav */
        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            display: flex;
            justify-content: space-around;
            padding: 12px 0 24px; /* Extra padding for iOS home bar */
            box-shadow: 0 -5px 15px rgba(0,0,0,0.05);
            z-index: 2000;
            border-top: 1px solid var(--gray-100);
        }
        .mobile-nav a {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--gray-500);
            font-size: 0.7rem;
            transition: var(--transition);
        }
        .mobile-nav a i { font-size: 1.5rem; margin-bottom: 2px; }
        .mobile-nav a.active { color: var(--primary); font-weight: 700; }
        
        /* Product Card Styles */
        .product-card {
            background: white;
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid var(--gray-100);
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }
        .product-card-image {
            position: relative;
            padding-top: 100%; /* 1:1 Aspect Ratio */
            overflow: hidden;
            background: var(--gray-100);
        }
        .product-card-image img {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            object-fit: contain;
            transition: var(--transition);
        }
        .product-card:hover .product-card-image img { transform: scale(1.08); }

        .product-badge {
            position: absolute;
            top: 10px; left: 10px;
            display: flex; flex-direction: column; gap: 5px;
            z-index: 2;
        }
        .badge-sale, .badge-preorder, .badge-outofstock {
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-sale { background: var(--accent); color: white; }
        .badge-preorder { background: var(--secondary); color: var(--dark); }
        .badge-outofstock { background: var(--gray-400); color: white; }

        .product-card-body { padding: 15px; flex-grow: 1; }
        .product-category { font-size: 0.75rem; color: var(--gray-500); text-transform: uppercase; margin-bottom: 5px; }
        .product-name { 
            font-size: 1rem; font-weight: 600; margin-bottom: 10px;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 2.4em;
        }
        .product-name a { text-decoration: none; color: var(--dark); transition: var(--transition); }
        .product-name a:hover { color: var(--primary); }

        .product-price { display: flex; align-items: baseline; gap: 8px; }
        .price-current { color: var(--primary); font-weight: 700; font-size: 1.1rem; }
        .price-original { color: var(--gray-400); text-decoration: line-through; font-size: 0.9rem; }

        .product-card-footer { padding: 0 15px 15px; }
        .btn-add-cart {
            width: 100%;
            background: var(--white);
            color: var(--primary);
            border: 1.5px solid var(--primary);
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 700;
            transition: var(--transition);
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-add-cart:hover:not(:disabled) { background: var(--primary); color: white; }
        .btn-add-cart:disabled { border-color: var(--gray-300); color: var(--gray-400); cursor: not-allowed; }

        @media (max-width: 576px) {
            .product-card-body { padding: 10px; }
            .product-card-footer { padding: 0 10px 10px; }
            .product-name { font-size: 0.9rem; margin-bottom: 5px; }
            .price-current { font-size: 1rem; }
            .btn-add-cart { font-size: 0.75rem; padding: 6px 8px; }
        }

        /* Nav Icon Buttons */
        .nav-icon-btn {
            font-size: 1.5rem;
            color: var(--dark);
            transition: var(--transition);
            display: flex;
            align-items: center;
        }
        .nav-icon-btn:hover {
            color: var(--primary);
        }

        /* General Mobile Fixes */
        .container { padding-left: 20px; padding-right: 20px; }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container" style="overflow: visible;">
            <div class="d-flex justify-content-between align-items-center" style="overflow: visible;">
                <div class="top-bar-left d-flex align-items-center gap-3" style="overflow: visible;">
                    <span class="top-bar-text d-none d-sm-inline">
                        @php $threshold = \App\Models\Setting::get('free_delivery_threshold', 2000); @endphp
                        <i class="bi bi-truck"></i> {{ app()->getLocale() == 'bn' ? 'সারাদেশে ডেলিভারি | ৳'.number_format($threshold).'+ অর্ডারে ফ্রি ডেলিভারি' : 'Delivery Nationwide | Free Delivery on Order ৳'.number_format($threshold).'+' }}
                    </span>
                    <div class="custom-lang-dropdown">
                        <button class="lang-trigger">
                            <i class="bi bi-globe"></i>
                            <span>{{ app()->getLocale() == 'bn' ? 'বাংলা' : 'English' }}</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="lang-menu shadow-lg">
                            <a class="{{ app()->getLocale() == 'en' ? 'active' : '' }}" href="{{ route('lang.switch', 'en') }}">English</a>
                            <a class="{{ app()->getLocale() == 'bn' ? 'active' : '' }}" href="{{ route('lang.switch', 'bn') }}">বাংলা</a>
                        </div>
                    </div>
                </div>
                <a href="tel:{{ \App\Models\Setting::get('phone', '01716-952365') }}" class="top-bar-link">
                    <i class="bi bi-whatsapp"></i> {{ \App\Models\Setting::get('phone', '01716-952365') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                @include('partials.brand')
            </a>

            <div class="d-flex align-items-center d-lg-none">
                <!-- Top toggles removed to prefer the bottom mobile-nav -->
            </div>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                            {{ app()->getLocale() == 'bn' ? 'হোম' : 'Home' }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('shop') ? 'active' : '' }}" href="{{ route('shop') }}">
                            {{ app()->getLocale() == 'bn' ? 'শপ' : 'Shop' }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('pages.show', 'about-us') }}">
                            {{ app()->getLocale() == 'bn' ? 'সম্পর্কে' : 'About' }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">
                            {{ app()->getLocale() == 'bn' ? 'যোগাযোগ' : 'Contact' }}
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <form action="{{ route('shop') }}" method="GET" class="search-form d-none d-lg-flex">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="{{ app()->getLocale() == 'bn' ? 'পণ্য খুঁজুন...' : 'Search Products...' }}" value="{{ request('search') }}">
                            <button class="btn btn-search" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>

                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('cart.index') }}" class="nav-cart-btn position-relative" id="desktopCartBtn">
                            <i class="bi bi-cart3"></i>
                            <span data-vue="CartBadge"></span>
                        </a>
                        @auth
                            <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('customer.dashboard') }}" class="nav-icon-btn ms-2">
                                <i class="bi bi-person-circle"></i>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="nav-icon-btn ms-2">
                                <i class="bi bi-person"></i>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Search Bar (Only shown on mobile) -->
    <div class="container d-lg-none mt-2">
        <form action="{{ route('shop') }}" method="GET" class="mobile-search-form">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="{{ app()->getLocale() == 'bn' ? 'কি খুঁজছেন?' : 'What are you looking for?' }}" value="{{ request('search') }}">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    @endif
    @if(session('error'))
    <div class="container mt-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    @endif

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-nav d-lg-none">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
            <i class="bi bi-house{{ request()->routeIs('home') ? '-fill' : '' }}"></i>
            <span>{{ app()->getLocale() == 'bn' ? 'হোম' : 'Home' }}</span>
        </a>
        <a href="{{ route('shop') }}" class="{{ request()->routeIs('shop') ? 'active' : '' }}">
            <i class="bi bi-grid{{ request()->routeIs('shop') ? '-fill' : '' }}"></i>
            <span>{{ app()->getLocale() == 'bn' ? 'শপ' : 'Shop' }}</span>
        </a>
        <a href="{{ route('cart.index') }}" class="{{ request()->routeIs('cart.index') ? 'active' : '' }} position-relative">
            <i class="bi bi-cart{{ request()->routeIs('cart.index') ? '-fill' : '3' }}"></i>
            <span data-vue="CartBadge" data-props="{{ json_encode(['extraClass' => 'badge rounded-pill bg-danger']) }}"></span>
            <span>{{ app()->getLocale() == 'bn' ? 'কার্ট' : 'Cart' }}</span>
        </a>
        <a href="{{ auth()->check() ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('customer.dashboard')) : route('login') }}" class="{{ request()->routeIs('login') || request()->routeIs('customer.dashboard') || request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-person{{ request()->routeIs('login') || request()->routeIs('customer.dashboard') || request()->routeIs('admin.dashboard') ? '-fill' : '' }}"></i>
            <span>{{ auth()->check() ? (app()->getLocale() == 'bn' ? 'অ্যাকাউন্ট' : 'Account') : (app()->getLocale() == 'bn' ? 'লগইন' : 'Login') }}</span>
        </a>
        @if($navWhatsapp = \App\Support\Whatsapp::shopUrl())
        <a href="{{ $navWhatsapp }}" target="_blank" rel="noopener">
            <i class="bi bi-whatsapp"></i>
            <span>{{ app()->getLocale() == 'bn' ? 'কল' : 'Call' }}</span>
        </a>
        @endif
    </div>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Style additions for "WOW" factor -->
    @push('styles')
    <style>
        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: white;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1040;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }
        .mobile-nav a {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--gray-500);
            font-size: 0.75rem;
            flex: 1;
            transition: var(--transition);
        }
        .mobile-nav a i { font-size: 1.4rem; margin-bottom: 2px; }
        .mobile-nav a.active { color: var(--primary); font-weight: 600; }

        /* Modern Design System Enhancements */
        .search-form .input-group {
            background: var(--gray-100);
            border-radius: 30px;
            padding: 3px 6px;
            border: 2px solid transparent;
            transition: var(--transition);
        }
        .search-form .input-group:focus-within {
            background: white;
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
        }
        .search-form .form-control {
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 10px 20px;
            font-size: 0.95rem;
            width: 280px;
        }
        .search-form .btn-search {
            color: var(--primary);
            border: none;
            background: transparent;
            font-size: 1.2rem;
            padding: 0 15px;
        }
        
        .mobile-search-form { margin-bottom: 15px; }
        .mobile-search-form .form-control {
            border-radius: var(--radius-md) 0 0 var(--radius-md);
            border-color: var(--gray-200);
            padding: 12px 18px;
        }
        .mobile-search-form .btn {
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
            padding: 0 20px;
        }
        
        /* Organic Background Patterns */
        .hero-section::before {
            content: "";
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(var(--primary-rgb), 0.05) 0%, transparent 70%);
            z-index: -1;
        }
        
        main { padding-bottom: 80px; }
        @media (min-width: 992px) { main { padding-bottom: 0; } }
    </style>
    @endpush


    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand">
                        @include('partials.brand', ['size' => 'lg', 'onDark' => true])
                    </div>
                    <p class="footer-desc">
                        {{ app()->getLocale() == 'bn' 
                           ? 'খাঁটি ও অর্গানিক পণ্যের অনলাইন বাজার। আম, খেজুর গুড়, ঘি, সরিষার তেল, মধু সহ সকল প্রাকৃতিক পণ্য সরাসরি চাঁপাই নবাবগঞ্জ থেকে সরবরাহ করা হয়।' 
                           : 'Online market for pure and organic products. We supply mangoes, jaggery, ghee, mustard oil, honey and all natural products directly from Chapainawabganj.' }}
                    </p>
                    @php
                        // Driven by Site Settings; an empty field simply drops its icon.
                        $whatsappUrl = \App\Support\Whatsapp::shopUrl();
                        $socials = array_filter([
                            'facebook' => \App\Models\Setting::get('facebook'),
                            'instagram' => \App\Models\Setting::get('instagram'),
                            'tiktok' => \App\Models\Setting::get('tiktok'),
                            'youtube' => \App\Models\Setting::get('youtube'),
                            'whatsapp' => $whatsappUrl,
                        ]);
                    @endphp
                    @if($socials)
                    <div class="footer-social">
                        @foreach($socials as $network => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($network) }}">
                                <i class="bi bi-{{ $network }}"></i>
                            </a>
                        @endforeach
                    </div>
                    @endif
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="footer-title">{{ app()->getLocale() == 'bn' ? 'লিঙ্ক' : 'Quick Links' }}</h5>
                    <ul class="footer-links">
                        <li><a href="{{ route('home') }}">{{ app()->getLocale() == 'bn' ? 'হোম' : 'Home' }}</a></li>
                        <li><a href="{{ route('shop') }}">{{ app()->getLocale() == 'bn' ? 'দোকান' : 'Shop' }}</a></li>
                        <li><a href="{{ route('pages.show', 'about-us') }}">{{ app()->getLocale() == 'bn' ? 'আমাদের সম্পর্কে' : 'About Us' }}</a></li>
                        <li><a href="{{ route('contact') }}">{{ app()->getLocale() == 'bn' ? 'যোগাযোগ' : 'Contact' }}</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-title">{{ app()->getLocale() == 'bn' ? 'পলিসি' : 'Legal' }}</h5>
                    <ul class="footer-links">
                        <li><a href="{{ route('pages.show', 'terms-and-conditions') }}">{{ app()->getLocale() == 'bn' ? 'টার্মস ও কন্ডিশন' : 'Terms & Conditions' }}</a></li>
                        <li><a href="{{ route('pages.show', 'privacy-policy') }}">{{ app()->getLocale() == 'bn' ? 'প্রাইভেসি পলিসি' : 'Privacy Policy' }}</a></li>
                        <li><a href="{{ route('pages.show', 'shipping-policy') }}">{{ app()->getLocale() == 'bn' ? 'শিপিং পলিসি' : 'Shipping Policy' }}</a></li>
                        <li><a href="{{ route('pages.show', 'return-policy') }}">{{ app()->getLocale() == 'bn' ? 'রিটার্ন পলিসি' : 'Return Policy' }}</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-title">{{ app()->getLocale() == 'bn' ? 'যোগাযোগ' : 'Contact Us' }}</h5>
                    <ul class="footer-contact">
                        <li><i class="bi bi-telephone"></i> {{ \App\Models\Setting::get('phone', '01716-952365') }}</li>
                        <li><i class="bi bi-whatsapp"></i> WhatsApp: {{ \App\Models\Setting::get('whatsapp', '01716-952365') }}</li>
                        <li><i class="bi bi-geo-alt"></i> {{ \App\Models\Setting::get('address', 'চাঁপাই নবাবগঞ্জ, রাজশাহী') }}</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('site_title', 'MohiPure') }} | {{ app()->getLocale() == 'bn' ? 'সর্বস্বত্ব সংরক্ষিত' : 'All Rights Reserved' }}</p>
            </div>
        </div>
    </footer>

    <!-- Floating chat buttons (Admin > Settings > WhatsApp & Messenger) -->
    @include('partials.chat-float')

    <!-- Toast Notifications (Vue) -->
    <div data-vue="CartToast"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
