<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mango Hut – খাঁটি ও অর্গানিক পণ্যের অনলাইন বাজার')</title>
    <meta name="description" content="@yield('meta_description', 'আম, খেজুর গুড়, ঘি, সরিষার তেল, আমসত্ত্ব সহ সকল খাঁটি ও অর্গানিক পণ্যের অনলাইন বাজার। সরাসরি চাঁপাই নবাবগঞ্জ থেকে আপনার দোরগোড়ায়।')">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <style>
        :root {
            --primary: #2d6a4f;
            --primary-light: #40916c;
            --primary-dark: #1b4332;
            --secondary: #f4a261;
            --accent: #e76f51;
            --light: #f8f9fa;
            --dark: #1a1a2e;
            --gray-100: #f1f3f5;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --white: #ffffff;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 20px;
        }

        body {
            font-family: 'Hind Siliguri', sans-serif;
            color: #333;
            background-color: #fdfdfd;
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
            .top-bar .container > .d-flex { flex-direction: column; gap: 8px; }
        }

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
                        <i class="bi bi-truck"></i> {{ app()->getLocale() == 'bn' ? 'সারাদেশে ডেলিভারি | ৳২০০০+ অর্ডারে ফ্রি ডেলিভারি' : 'Delivery Nationwide | Free Delivery on Order ৳2000+' }}
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
                @if($logo = \App\Models\Setting::where('key', 'logo')->first())
                    <img src="{{ asset('storage/' . $logo->value_en) }}" alt="Logo" height="40" class="me-2">
                @else
                    <span class="brand-icon">🥭</span>
                    <span class="brand-text">
                        @php $title = \App\Models\Setting::get('site_title', 'Mango Hut'); @endphp
                        {{ $title }}
                    </span>
                @endif
            </a>

            <div class="d-flex align-items-center gap-2 d-lg-none">
                <a href="{{ route('cart.index') }}" class="nav-cart-btn position-relative">
                    <i class="bi bi-cart3"></i>
                    <span class="cart-badge" id="mobileCartBadge">0</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
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
                            <span class="cart-badge" id="desktopCartBadge">0</span>
                        </a>
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
            </div>
        </div>
    </nav>

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
            <span class="cart-badge badge rounded-pill bg-danger" id="mobileNavBadge">0</span>
            <span>{{ app()->getLocale() == 'bn' ? 'কার্ট' : 'Cart' }}</span>
        </a>
        <a href="https://wa.me/{{ \App\Models\Setting::get('whatsapp', '8801716952365') }}" target="_blank">
            <i class="bi bi-whatsapp"></i>
            <span>{{ app()->getLocale() == 'bn' ? 'কল' : 'Call' }}</span>
        </a>
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
            background: radial-gradient(circle, rgba(45, 106, 79, 0.05) 0%, transparent 70%);
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
                        <span class="brand-icon">🥭</span>
                        <span class="brand-text">Mango<span class="brand-highlight">Hut</span></span>
                    </div>
                    <p class="footer-desc">
                        {{ app()->getLocale() == 'bn' 
                           ? 'খাঁটি ও অর্গানিক পণ্যের অনলাইন বাজার। আম, খেজুর গুড়, ঘি, সরিষার তেল, মধু সহ সকল প্রাকৃতিক পণ্য সরাসরি চাঁপাই নবাবগঞ্জ থেকে সরবরাহ করা হয়।' 
                           : 'Online market for pure and organic products. We supply mangoes, jaggery, ghee, mustard oil, honey and all natural products directly from Chapainawabganj.' }}
                    </p>
                    <div class="footer-social">
                        <a href="https://www.facebook.com/mangohutt" target="_blank"><i class="bi bi-facebook"></i></a>
                        <a href="https://wa.me/8801716952365" target="_blank"><i class="bi bi-whatsapp"></i></a>
                        <a href="https://www.youtube.com/@mangohut7818" target="_blank"><i class="bi bi-youtube"></i></a>
                    </div>
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
                <p>&copy; {{ date('Y') }} Mango Hut | {{ app()->getLocale() == 'bn' ? 'সর্বস্বত্ব সংরক্ষিত' : 'All Rights Reserved' }}</p>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp Button -->
    <a href="https://wa.me/{{ \App\Models\Setting::get('whatsapp', '8801716952365') }}?text={{ app()->getLocale() == 'bn' ? 'হ্যালো! আমি আপনার ওয়েবসাইট থেকে অর্ডার করতে চাই।' : 'Hello! I want to order from your website.' }}" class="whatsapp-float" target="_blank" id="whatsappFloat">
        <i class="bi bi-whatsapp"></i>
    </a>

    <!-- Toast Notification -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
        <div id="cartToast" class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="cartToastBody">{{ app()->getLocale() == 'bn' ? 'কার্টে যোগ করা হয়েছে!' : 'Added to cart!' }}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        // CSRF Token for AJAX
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // Update cart badge on page load
        function updateCartBadge() {
            $.get('{{ route("cart.count") }}', function(data) {
                $('#desktopCartBadge, #mobileCartBadge').text(data.count);
                if (data.count > 0) {
                    $('#desktopCartBadge, #mobileCartBadge').addClass('show');
                } else {
                    $('#desktopCartBadge, #mobileCartBadge').removeClass('show');
                }
            });
        }

        function showToast(message, type = 'success') {
            const toast = $('#cartToast');
            toast.removeClass('bg-success bg-danger bg-warning').addClass('bg-' + type);
            $('#cartToastBody').text(message);
            new bootstrap.Toast(toast[0]).show();
        }

        // Add to cart function
        function addToCart(productId, variantId, qty = 1) {
            $.post('{{ route("cart.add") }}', {
                product_id: productId,
                variant_id: variantId,
                quantity: qty
            }, function(data) {
                if (data.success) {
                    showToast(data.message);
                    updateCartBadge();
                } else {
                    showToast(data.message, 'danger');
                }
            }).fail(function() {
                showToast('{{ app()->getLocale() == "bn" ? "কিছু ভুল হয়েছে!" : "Something went wrong!" }}', 'danger');
            });
        }

        $(document).ready(function() {
            updateCartBadge();

            // Navbar scroll effect
            $(window).scroll(function() {
                if ($(this).scrollTop() > 50) {
                    $('#mainNavbar').addClass('scrolled');
                } else {
                    $('#mainNavbar').removeClass('scrolled');
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
