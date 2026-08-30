{{--
    The layout a campaign page lives in.

    Deliberately not layouts.frontend: no navbar, no footer menu, no search, no
    cart — nothing a visitor who arrived from an ad could click instead of
    ordering. It also loads none of the storefront's CSS or JavaScript, because
    the traffic is mobile and paid for, and every extra request is money.
--}}
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $page->meta_title ?: $page->headline)</title>

    @if($page->meta_description)
        <meta name="description" content="{{ $page->meta_description }}">
    @endif

    {{-- Campaign pages are hidden from search by default so they do not
         compete with the real product pages. --}}
    <meta name="robots" content="{{ $page->noindex ? 'noindex, nofollow' : 'index, follow' }}">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="bn_BD">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $page->meta_title ?: $page->headline }}">
    @if($page->meta_description)
        <meta property="og:description" content="{{ $page->meta_description }}">
    @endif
    @if($page->ogImageUrl())
        <meta property="og:image" content="{{ $page->ogImageUrl() }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif

    @include('partials.favicon')

    @include('partials.meta-pixel', ['pixelId' => $page->pixelId(), 'events' => $pixelEvents ?? []])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/brand.css') }}" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            font-family: 'Hind Siliguri', system-ui, sans-serif;
            color: var(--dark, #1c2415);
            background: var(--cream, #fcfdf5);
            font-size: 1rem;
            line-height: 1.7;
            padding-bottom: 0;
        }

        /* Room for the sticky bar, only on the pages that have one. */
        body.has-sticky { padding-bottom: 84px; }

        img { max-width: 100%; height: auto; display: block; }
        a { color: var(--primary, #3d8202); }
        [hidden] { display: none !important; }

        /* One column at every width. .lp-wrap is the measure and it is the only
           thing that changes between a phone and a desktop — the page is never
           rearranged, only resized, so there is one layout to get right. */
        .lp-shell { width: 100%; }
        .lp-wrap { max-width: 620px; margin: 0 auto; padding: 0 16px; }

        /* The sticky header would otherwise sit on top of whatever an
           in-page link jumps to. */
        [id] { scroll-margin-top: 72px; }

        /* ---------------------------------------------------------- header */

        .lp-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            background: #fff;
            border-bottom: 1px solid var(--cream-dark, #f0f6e2);
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .lp-header .brand-logo { height: 34px; width: auto; }
        .lp-call {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            font-size: .9rem;
            color: var(--primary-dark, #2f6b02);
            text-decoration: none;
            border: 1.5px solid var(--primary-light, #70b502);
            border-radius: 999px;
            padding: 5px 14px;
            white-space: nowrap;
        }

        /* ----------------------------------------------------------- blocks */

        .lp-section { padding: 26px 0; }
        .lp-section + .lp-section { border-top: 1px solid var(--cream-dark, #f0f6e2); }

        .lp-h1 {
            font-size: clamp(1.5rem, 6vw, 2.1rem);
            line-height: 1.3;
            font-weight: 700;
            margin: 0 0 8px;
            color: var(--primary-darker, #1e4a01);
        }
        .lp-sub { font-size: 1.05rem; color: #4a5a3c; margin: 0 0 14px; }
        .lp-h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 0 14px;
            color: var(--primary-dark, #2f6b02);
        }

        .lp-badge {
            display: inline-block;
            background: var(--accent, #fda102);
            color: #3a2400;
            font-weight: 700;
            font-size: .85rem;
            border-radius: 999px;
            padding: 4px 14px;
            margin-bottom: 10px;
        }

        .lp-hero-media { border-radius: 14px; overflow: hidden; }
        .lp-hero-media iframe { width: 100%; aspect-ratio: 16/9; border: 0; display: block; }
        .lp-media-section { padding-top: 0; }

        /* The offer and the order form: the two panels the page exists for. */
        .lp-offer {
            background: #fff;
            border: 1px solid var(--cream-dark, #f0f6e2);
            border-radius: 16px;
            padding: 18px 16px;
        }
        /* A panel is a boundary of its own; a rule above it as well is one line
           too many. */
        .lp-section:has(.lp-offer) { border-top: 0; }

        .lp-card {
            background: #fff;
            border: 1px solid var(--cream-dark, #f0f6e2);
            border-radius: 14px;
            padding: 16px;
        }

        /* ----------------------------------------------------------- price */

        .lp-price { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; margin-bottom: 4px; }
        .lp-price-label { font-size: .9rem; color: #7c876f; }
        .lp-price-now { font-size: 2rem; font-weight: 700; color: var(--primary, #3d8202); }
        .lp-price-was { font-size: 1.1rem; color: #8a9580; text-decoration: line-through; }
        .lp-save {
            background: #fdeddb;
            color: var(--accent-text, #b85600);
            font-weight: 700;
            font-size: .85rem;
            border-radius: 6px;
            padding: 2px 8px;
        }

        /* --------------------------------------------------------- packages */

        .lp-pack {
            display: flex;
            align-items: center;
            gap: 12px;
            border: 2px solid var(--cream-dark, #f0f6e2);
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 10px;
            background: #fff;
            cursor: pointer;
        }
        .lp-pack:has(input:checked) { border-color: var(--primary, #3d8202); background: #f4faec; }
        .lp-pack input { width: 20px; height: 20px; accent-color: var(--primary, #3d8202); flex: none; }
        .lp-pack img { width: 52px; height: 52px; object-fit: cover; border-radius: 8px; flex: none; }
        .lp-pack-body { flex: 1 1 auto; min-width: 0; }
        /* A long Bengali product name must wrap rather than push the price off
           the edge of a 320px screen. */
        .lp-pack-name { font-weight: 600; line-height: 1.4; overflow-wrap: anywhere; }
        .lp-pack-price { font-weight: 700; color: var(--primary, #3d8202); white-space: nowrap; }
        .lp-pack-was { color: #8a9580; text-decoration: line-through; font-size: .85rem; margin-left: 6px; }
        .lp-pack.is-out { opacity: .55; cursor: not-allowed; }

        .lp-qty { display: flex; align-items: center; gap: 8px; margin-top: 10px; }
        .lp-qty select, .lp-field input, .lp-field select, .lp-field textarea {
            font-family: inherit;
            font-size: 1rem;
            border: 1.5px solid var(--gray-300, #dee2e6);
            border-radius: 10px;
            padding: 11px 12px;
            width: 100%;
            background: #fff;
            color: inherit;
        }
        .lp-qty select { width: auto; padding-right: 30px; }
        .lp-field input:focus, .lp-field select:focus, .lp-field textarea:focus {
            outline: none;
            border-color: var(--primary-light, #70b502);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb, 61,130,2), .15);
        }

        /* ------------------------------------------------------------ lists */

        .lp-features { list-style: none; margin: 0; padding: 0; }
        .lp-features li {
            position: relative;
            padding-left: 30px;
            margin-bottom: 8px;
        }
        .lp-features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            top: 0;
            width: 21px;
            height: 21px;
            line-height: 21px;
            text-align: center;
            border-radius: 50%;
            background: var(--primary, #3d8202);
            color: #fff;
            font-size: .75rem;
            font-weight: 700;
        }

        .lp-review { border-left: 3px solid var(--accent-gold, #fbcf02); padding: 2px 0 2px 12px; margin-bottom: 14px; }
        .lp-review-name { font-weight: 700; font-size: .92rem; }
        .lp-stars { color: var(--accent, #fda102); font-size: .85rem; letter-spacing: 1px; }

        .lp-faq { border-bottom: 1px solid var(--cream-dark, #f0f6e2); }
        .lp-faq summary {
            cursor: pointer;
            font-weight: 600;
            padding: 12px 0;
            list-style: none;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .lp-faq summary::-webkit-details-marker { display: none; }
        .lp-faq summary::after { content: '+'; font-weight: 700; color: var(--primary, #3d8202); }
        .lp-faq[open] summary::after { content: '−'; }
        .lp-faq p { margin: 0 0 12px; color: #4a5a3c; }

        /* This block is whatever the admin pasted into the editor, so it is the
           one place a stray table or a wide embed can push the page sideways. */
        .lp-body-copy { overflow-x: auto; }
        .lp-body-copy img { border-radius: 12px; margin: 10px 0; }
        .lp-body-copy h2, .lp-body-copy h3 { color: var(--primary-dark, #2f6b02); }
        .lp-body-copy table { width: 100%; border-collapse: collapse; }
        .lp-body-copy iframe, .lp-body-copy video { max-width: 100%; }

        /* ----------------------------------------------------------- forms */

        .lp-field { margin-bottom: 12px; }
        .lp-field label { display: block; font-weight: 600; font-size: .92rem; margin-bottom: 5px; }
        .lp-field .lp-error { color: #c62828; font-size: .86rem; margin-top: 4px; }
        .lp-field input.is-bad, .lp-field select.is-bad { border-color: #c62828; }

        .lp-btn {
            display: block;
            width: 100%;
            border: 0;
            border-radius: 12px;
            background: var(--primary, #3d8202);
            color: #fff;
            font-family: inherit;
            font-size: 1.15rem;
            font-weight: 700;
            padding: 15px 18px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }
        .lp-btn:hover { background: var(--primary-dark, #2f6b02); }
        .lp-btn:disabled { background: #9db98a; cursor: not-allowed; }

        .lp-total {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: .96rem;
        }
        .lp-total.is-grand {
            border-top: 1px dashed var(--gray-300, #dee2e6);
            margin-top: 6px;
            padding-top: 10px;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--primary-dark, #2f6b02);
        }

        .lp-alert {
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 14px;
            font-weight: 600;
        }
        .lp-alert-bad { background: #fdecea; color: #a52117; }
        .lp-alert-note { background: #fff6e5; color: #7a4c00; }
        .lp-alert-info { background: #eef6e4; color: var(--primary-dark, #2f6b02); }

        /* -------------------------------------------------------- urgency */

        .lp-countdown {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin: 12px 0;
        }
        .lp-countdown div {
            background: var(--primary-darker, #1e4a01);
            color: #fff;
            border-radius: 10px;
            padding: 6px 10px;
            min-width: 58px;
            text-align: center;
            line-height: 1.2;
        }
        .lp-countdown b { display: block; font-size: 1.3rem; }
        .lp-countdown span { font-size: .72rem; opacity: .8; }

        /* ------------------------------------------------------ sticky bar */

        .lp-sticky {
            position: fixed;
            left: 0; right: 0; bottom: 0;
            background: #fff;
            border-top: 1px solid var(--cream-dark, #f0f6e2);
            box-shadow: 0 -4px 18px rgba(0, 0, 0, .08);
            padding: 10px 16px calc(10px + env(safe-area-inset-bottom));
            z-index: 30;
        }
        .lp-sticky .lp-wrap { display: flex; align-items: center; gap: 12px; padding: 0; }
        .lp-sticky-label { font-size: .72rem; color: #7c876f; line-height: 1; }
        .lp-sticky-price { font-weight: 700; font-size: 1.15rem; color: var(--primary-dark, #2f6b02); white-space: nowrap; }
        .lp-sticky .lp-btn { width: auto; flex: 1 1 auto; font-size: 1rem; padding: 12px 16px; }

        .lp-foot {
            text-align: center;
            font-size: .85rem;
            color: #7c876f;
            padding: 22px 16px 6px;
        }

        .lp-preview {
            background: #7a4c00;
            color: #fff;
            text-align: center;
            font-weight: 700;
            font-size: .9rem;
            padding: 7px 16px;
        }

        /*
           Small phones. The hero runs edge to edge — a 16px gutter around the
           one picture that has to sell the product wastes the width the
           picture needed.
        */
        @media (max-width: 575.98px) {
            .lp-hero-media {
                margin-left: -16px;
                margin-right: -16px;
                border-radius: 0;
            }
        }

        @media (min-width: 576px) {
            body { font-size: 1.05rem; }
            .lp-section { padding: 32px 0; }
            .lp-offer { padding: 24px; }
        }

        /*
           Desktop: the offer stops being something you scroll to and becomes a
           panel that stays put. The pictures and the reasons to buy run down
           the left; the packages and the form sit on the right, in view the
           whole way down — which is what the sticky bar is doing on a phone,
           so that bar goes away here.
        */
        /*
           Desktop is the same page, wider and larger — not a different one.
           620px of body text on a 1080p monitor reads as a leftover phone
           screen, and a second column would put the order form somewhere the
           eye does not go next.
        */
        @media (min-width: 992px) {
            body { font-size: 1.1rem; }
            body.has-sticky { padding-bottom: 92px; }

            .lp-wrap { max-width: 820px; padding: 0 24px; }
            .lp-section { padding: 44px 0; }
            .lp-offer { padding: 32px; }

            .lp-h1 { font-size: 2.6rem; }
            .lp-sub { font-size: 1.25rem; }
            .lp-h2 { font-size: 1.55rem; }
            .lp-price-now { font-size: 2.6rem; }
            .lp-price-was { font-size: 1.3rem; }
            .lp-save { font-size: .95rem; }

            .lp-pack { padding: 14px 16px; gap: 16px; }
            .lp-pack img { width: 64px; height: 64px; }
            .lp-header { padding: 12px 24px; }
            .lp-header .brand-logo { height: 40px; }
        }
    </style>

    @vite(['resources/js/landing.js'])
    @stack('styles')
</head>
<body class="{{ ($takingOrders ?? false) ? 'has-sticky' : '' }}">
    @if($preview ?? false)
        <div class="lp-preview">প্রিভিউ — এই পেজটি এখনো লাইভ নয়, শুধু আপনি দেখতে পাচ্ছেন।</div>
    @endif

    <header class="lp-header">
        <a href="{{ route('home') }}" aria-label="{{ \App\Models\Setting::get('site_title', 'MohiPure') }}">
            @include('partials.brand')
        </a>

        @php $phone = \App\Models\Setting::get('phone'); @endphp
        @if($phone)
            <a class="lp-call" href="tel:{{ preg_replace('/\s+/', '', $phone) }}">
                ☎ {{ $phone }}
            </a>
        @endif
    </header>

    @yield('content')

    <footer class="lp-foot">
        © {{ date('Y') }} {{ \App\Models\Setting::get('site_title', 'MohiPure') }} ·
        <a href="{{ route('home') }}">মূল ওয়েবসাইট</a>
    </footer>

    @yield('sticky')
    @stack('scripts')
</body>
</html>
