<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('partials.favicon')

    @php
        $seoTitle = trim(
            $__env->yieldContent(
                'title',
                \App\Support\SeoSettings::get('seo_meta_title') ?:
                \App\Models\Setting::get('site_title', 'BaburhashiBD')
            )
        );
        $seoDescription = trim(
            $__env->yieldContent('meta_description', \App\Support\SeoSettings::get('seo_meta_description') ?: '')
        );
        $seoKeywords = \App\Support\SeoSettings::get('seo_meta_keywords');
        $seoImage = trim($__env->yieldContent('og_image', \App\Support\SeoSettings::ogImageUrl() ?: ''));
        $seoVerification = \App\Support\SeoSettings::get('seo_google_site_verification');
        $analyticsId = \App\Support\SeoSettings::analyticsId();
    @endphp

    <title>{{ $seoTitle }}</title>
    @if ($seoDescription)
        <meta name="description" content="{{ $seoDescription }}">
    @endif
    @if ($seoKeywords)
        <meta name="keywords" content="{{ $seoKeywords }}">
    @endif
    <meta name="robots" content="{{ \App\Support\SeoSettings::robots() }}">
    <link rel="canonical" href="{{ url()->current() }}">
    @if ($seoVerification)
        <meta name="google-site-verification" content="{{ $seoVerification }}">
    @endif

    {{-- Open Graph: what Facebook, WhatsApp and Messenger show when a link is shared --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ \App\Models\Setting::get('site_title', 'BaburhashiBD') }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'bn' ? 'bn_BD' : 'en_US' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    @if ($seoDescription)
        <meta property="og:description" content="{{ $seoDescription }}">
    @endif
    @if ($seoImage)
        <meta property="og:image" content="{{ $seoImage }}">
    @endif

    <meta name="twitter:card" content="{{ $seoImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    @if ($seoDescription)
        <meta name="twitter:description" content="{{ $seoDescription }}">
    @endif
    @if ($seoImage)
        <meta name="twitter:image" content="{{ $seoImage }}">
    @endif

    @if ($analyticsId)
        {{-- Only loaded once a measurement ID is saved in SEO settings --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analyticsId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag('js', new Date());
            gtag('config', @json($analyticsId));
        </script>
    @endif

    @include('partials.meta-pixel', ['pixelId' => \App\Support\SeoSettings::facebookPixelId()])

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="{{ asset('css/brand.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/storefront.css') }}" rel="stylesheet">

    {{-- Runtime config for resources/js/storefront.js --}}
    @php
        $storefrontConfig = [
            'routes' => [
                'add' => route('cart.add'),
                'update' => route('cart.update'),
                'remove' => route('cart.remove'),
                'count' => route('cart.count'),
                'mini' => route('cart.mini'),
                'couponApply' => route('cart.coupon.apply'),
                'couponRemove' => route('cart.coupon.remove')
            ],
            'locale' => app()->getLocale(),
            'freeDeliveryThreshold' => (float) \App\Models\Setting::get('free_delivery_threshold', 2000),
            'whatsapp' => \App\Models\Setting::get('whatsapp', '8801716952365'),
            'strings' => [
                'added' => app()->getLocale() == 'bn' ? 'কার্টে যোগ করা হয়েছে!' : 'Added to cart!',
                'removed' => app()->getLocale() == 'bn' ? 'পণ্যটি সরানো হয়েছে' : 'Item removed',
                'error' => app()->getLocale() == 'bn' ? 'কিছু ভুল হয়েছে!' : 'Something went wrong!'
            ]
        ];
    @endphp
    <script type="application/json" id="storefront-config">{!! json_encode($storefrontConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>

    @vite(['resources/js/storefront.js'])
    @stack('styles')
</head>

<body>
    {{-- ===== Info strip ===== --}}
    <div class="pc-topbar">
        <div class="container">
            <div class="pc-topbar-inner">
                @php
                    $threshold = \App\Models\Setting::get('free_delivery_threshold', 2000);
                    // Settings > Storefront Text. :threshold stands in for the
                    // free-delivery figure so the number never has to be retyped
                    // here when the shipping rule changes.
                    $topbarNote =
                        \App\Models\Setting::get('topbar_note') ?:
                        (app()->getLocale() == 'bn'
                            ? 'সারাদেশে ডেলিভারি | ৳:threshold+ অর্ডারে ফ্রি ডেলিভারি'
                            : 'Delivery nationwide | Free delivery over ৳:threshold');
                @endphp
                <span class="pc-topbar-note">
                    <i class="bi bi-truck"></i>
                    {{ str_replace(':threshold', number_format($threshold), $topbarNote) }}
                </span>
                <div class="pc-topbar-right">
                    <div class="pc-lang">
                        <button type="button" class="pc-lang-trigger">
                            <i class="bi bi-globe2"></i>
                            <span>{{ app()->getLocale() == 'bn' ? 'বাংলা' : 'English' }}</span>
                            <i class="bi bi-chevron-down" style="font-size:.65rem"></i>
                        </button>
                        <div class="pc-lang-menu">
                            <a class="{{ app()->getLocale() == 'en' ? 'active' : '' }}"
                                href="{{ route('lang.switch', 'en') }}">English</a>
                            <a class="{{ app()->getLocale() == 'bn' ? 'active' : '' }}"
                                href="{{ route('lang.switch', 'bn') }}">বাংলা</a>
                        </div>
                    </div>
                    <span class="pc-topbar-sep d-none d-sm-block"></span>
                    <a href="tel:{{ \App\Models\Setting::get('phone', '+880 1XXX-XXXXXX') }}" class="pc-topbar-link">
                        <i class="bi bi-telephone-fill"></i>
                        <span>{{ \App\Models\Setting::get('phone', '+880 1XXX-XXXXXX') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Logo, search, account ===== --}}
    <header class="pc-header">
        <div class="container">
            <div class="pc-header-inner">
                <a href="{{ route('home') }}" class="pc-header-brand">
                    @include('partials.brand')
                </a>

                {{-- The search leaves the flow on small screens and reappears
                     as a full-width row of its own, just below. --}}
                <form action="{{ route('shop') }}" method="GET" class="pc-search d-none d-lg-block">
                    <div class="pc-search-box">
                        <select name="category" class="pc-search-cat"
                            aria-label="{{ app()->getLocale() == 'bn' ? 'ক্যাটাগরি' : 'Category' }}">
                            <option value="">{{ app()->getLocale() == 'bn' ? 'সব ক্যাটাগরি' : 'All Categories' }}
                            </option>
                            @foreach ($navCategories as $navCategory)
                                <option value="{{ $navCategory->slug }}" @selected(request('category') == $navCategory->slug)>
                                    {{ $navCategory->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="search" class="pc-search-input" value="{{ request('search') }}"
                            placeholder="{{ app()->getLocale() == 'bn' ? 'পণ্য খুঁজুন…' : 'Search for products…' }}">
                        <button class="pc-search-btn" type="submit"
                            aria-label="{{ app()->getLocale() == 'bn' ? 'খুঁজুন' : 'Search' }}">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>

                <div class="pc-header-actions">
                    @auth
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('customer.dashboard') }}"
                            class="pc-header-action">
                            <i class="bi bi-person-circle"></i>
                            <span class="pc-action-label">
                                <small>{{ app()->getLocale() == 'bn' ? 'স্বাগতম' : 'Welcome' }}</small>
                                <strong>{{ \Illuminate\Support\Str::limit(auth()->user()->name, 12) }}</strong>
                            </span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="pc-header-action">
                            <i class="bi bi-person"></i>
                            <span class="pc-action-label">
                                <small>{{ app()->getLocale() == 'bn' ? 'অ্যাকাউন্ট' : 'Account' }}</small>
                                <strong>{{ app()->getLocale() == 'bn' ? 'লগইন' : 'Login' }}</strong>
                            </span>
                        </a>
                    @endauth
                    <a href="{{ route('cart.index') }}" class="pc-header-action pc-header-cart">
                        <i class="bi bi-cart3"></i>
                        <span data-vue="CartBadge"></span>
                        <span class="pc-action-label">
                            <small>{{ app()->getLocale() == 'bn' ? 'আমার' : 'My' }}</small>
                            <strong>{{ app()->getLocale() == 'bn' ? 'কার্ট' : 'Cart' }}</strong>
                        </span>
                    </a>
                </div>
            </div>

            <form action="{{ route('shop') }}" method="GET" class="pc-mobile-search d-lg-none">
                <div class="pc-search-box">
                    <input type="text" name="search" class="pc-search-input" value="{{ request('search') }}"
                        placeholder="{{ app()->getLocale() == 'bn' ? 'কি খুঁজছেন?' : 'What are you looking for?' }}">
                    <button class="pc-search-btn" type="submit"
                        aria-label="{{ app()->getLocale() == 'bn' ? 'খুঁজুন' : 'Search' }}">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </header>

    {{-- ===== Category bar (desktop) ===== --}}
    <nav class="pc-navbar d-none d-lg-block">
        <div class="container">
            <div class="pc-navbar-inner">
                <div class="pc-catmenu">
                    <button type="button" class="pc-catmenu-btn">
                        <i class="bi bi-list"></i>
                        <span>{{ app()->getLocale() == 'bn' ? 'সব ক্যাটাগরি' : 'All Categories' }}</span>
                        <i class="bi bi-chevron-down pc-caret"></i>
                    </button>
                    <div class="pc-catmenu-panel">
                        @include('partials.category-list', ['categories' => $navCategories])
                    </div>
                </div>

                {{-- Admin > Settings > Storefront Blocks > Header Menu. --}}
                <ul class="pc-nav-links">
                    @foreach (\App\Models\SiteBlock::list('header_menu') as $item)
                        <li>
                            <a href="{{ $item->link }}" @class([
                                'pc-nav-hot' => $item->is_highlighted,
                                'active' => url()->current() === $item->link
                            ])
                                @if ($item->is_external) target="_blank" rel="noopener" @endif>
                                @if ($item->icon)
                                    <i class="bi bi-{{ $item->icon }}"></i>
                                @endif
                                {{ $item->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                <a href="tel:{{ \App\Models\Setting::get('phone', '+880 1XXX-XXXXXX') }}" class="pc-nav-hotline">
                    <i class="bi bi-headset"></i>
                    <span>{{ \App\Models\Setting::get('phone', '+880 1XXX-XXXXXX') }}</span>
                </a>
            </div>
        </div>
    </nav>
    <!-- Flash Messages -->
    @if (session('success'))
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif
    @if (session('error'))
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
        <a href="{{ route('cart.index') }}"
            class="{{ request()->routeIs('cart.index') ? 'active' : '' }} position-relative">
            <i class="bi bi-cart{{ request()->routeIs('cart.index') ? '-fill' : '3' }}"></i>
            <span data-vue="CartBadge"
                data-props="{{ json_encode(['extraClass' => 'badge rounded-pill bg-danger']) }}"></span>
            <span>{{ app()->getLocale() == 'bn' ? 'কার্ট' : 'Cart' }}</span>
        </a>
        <a href="{{ auth()->check() ? (auth()->user()->isAdmin() ? route('admin.dashboard') : route('customer.dashboard')) : route('login') }}"
            class="{{ request()->routeIs('login') || request()->routeIs('customer.dashboard') || request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i
                class="bi bi-person{{ request()->routeIs('login') || request()->routeIs('customer.dashboard') || request()->routeIs('admin.dashboard') ? '-fill' : '' }}"></i>
            <span>{{ auth()->check() ? (app()->getLocale() == 'bn' ? 'অ্যাকাউন্ট' : 'Account') : (app()->getLocale() == 'bn' ? 'লগইন' : 'Login') }}</span>
        </a>
        @if ($navWhatsapp = \App\Support\Whatsapp::shopUrl())
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


    {{-- ===== How to pay — Storefront Blocks > Payment Methods ===== --}}
    @php $payments = \App\Models\SiteBlock::list('payment'); @endphp
    @if ($payments->count())
        <div class="pc-payments">
            <div class="container">
                <div class="pc-payments-inner">
                    <span
                        class="pc-payments-label">{{ app()->getLocale() == 'bn' ? 'পেমেন্ট মাধ্যম:' : 'We accept:' }}</span>
                    @foreach ($payments as $payment)
                        <span class="pc-payment-chip"><i class="bi bi-{{ $payment->icon_name }}"></i>
                            {{ $payment->title }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand">
                        @include('partials.brand', ['size' => 'lg', 'onDark' => true])
                    </div>
                    {{-- Settings > Storefront Text; blank falls back to this copy. --}}
                    <p class="footer-desc">
                        {{ \App\Models\Setting::get('footer_desc') ?:
                            (app()->getLocale() == 'bn'
                                ? 'শিশুদের প্রিয় সবকিছুর অনলাইন শপ। পোশাক, ডায়াপার, খেলনা, ফিডিং সামগ্রী ও স্কুল সামগ্রী সহ সকল প্রয়োজনীয় পণ্য সারাদেশে সরবরাহ করা হয়।'
                                : 'Online shop for baby and kids essentials. We supply clothing, diapers, toys, feeding items and school supplies, delivered nationwide.') }}
                    </p>
                    @php
                        // Driven by Site Settings; an empty field simply drops its icon.
                        $whatsappUrl = \App\Support\Whatsapp::shopUrl();
                        $socials = array_filter([
                            'facebook' => \App\Models\Setting::get('facebook'),
                            'instagram' => \App\Models\Setting::get('instagram'),
                            'tiktok' => \App\Models\Setting::get('tiktok'),
                            'youtube' => \App\Models\Setting::get('youtube'),
                            'whatsapp' => $whatsappUrl
                        ]);
                    @endphp
                    @if ($socials)
                        <div class="footer-social">
                            @foreach ($socials as $network => $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener"
                                    aria-label="{{ ucfirst($network) }}">
                                    <i class="bi bi-{{ $network }}"></i>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-title">{{ app()->getLocale() == 'bn' ? 'ক্যাটাগরি' : 'Categories' }}</h5>
                    <ul class="footer-links">
                        @foreach ($navCategories->take(6) as $navCategory)
                            <li><a
                                    href="{{ route('shop', ['category' => $navCategory->slug]) }}">{{ $navCategory->name }}</a>
                            </li>
                        @endforeach
                        <li><a
                                href="{{ route('shop') }}">{{ app()->getLocale() == 'bn' ? 'সব পণ্য' : 'All Products' }}</a>
                        </li>
                    </ul>
                </div>
                @php $footerMenu = \App\Models\SiteBlock::list('footer_menu'); @endphp
                @if ($footerMenu->count())
                    <div class="col-lg-2 col-md-6">
                        <h5 class="footer-title">{{ app()->getLocale() == 'bn' ? 'আমার অ্যাকাউন্ট' : 'My Account' }}
                        </h5>
                        <ul class="footer-links">
                            @foreach ($footerMenu as $item)
                                <li>
                                    <a href="{{ $item->link }}"
                                        @if ($item->is_external) target="_blank" rel="noopener" @endif>
                                        {{ $item->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-title">{{ app()->getLocale() == 'bn' ? 'যোগাযোগ' : 'Contact Us' }}</h5>
                    <ul class="footer-contact">
                        <li><i class="bi bi-telephone"></i>
                            {{ \App\Models\Setting::get('phone', '+880 1XXX-XXXXXX') }}</li>
                        <li><i class="bi bi-whatsapp"></i> WhatsApp:
                            {{ \App\Models\Setting::get('whatsapp', '+880 1XXX-XXXXXX') }}</li>
                        <li><i class="bi bi-geo-alt"></i>
                            {{ \App\Models\Setting::get('address', 'Laksham, cumilla') }}</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="pc-footer-bottom-inner">
                    <p>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('site_title', 'BaburhashiBD') }} |
                        {{ app()->getLocale() == 'bn' ? 'সর্বস্বত্ব সংরক্ষিত' : 'All Rights Reserved' }}</p>
                    <div class="pc-footer-bottom-links">
                        @foreach (\App\Models\SiteBlock::list('footer_bottom') as $item)
                            <a href="{{ $item->link }}"
                                @if ($item->is_external) target="_blank" rel="noopener" @endif>
                                {{ $item->title }}
                            </a>
                        @endforeach
                    </div>
                </div>
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
