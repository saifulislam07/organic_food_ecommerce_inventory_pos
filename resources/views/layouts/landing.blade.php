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
    {{-- Baloo Da 2 is the rounded display face (it has Bengali glyphs); Hind
         Siliguri carries the body copy. One request for both. --}}
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/brand.css') }}" rel="stylesheet">
    {{-- After brand.css: a theme is nothing but custom properties redefined on
         <body>, and they only win if this file is the later one. --}}
    <link href="{{ asset('css/landing-themes.css') }}" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;

            /*
               One corner scale for the whole page, derived from the theme's
               --radius so a theme sets a single number and every panel, card
               and input moves together.
            */
            --lp-r-lg: calc(var(--radius, 12px) + 6px);
            --lp-r: var(--radius, 12px);
            --lp-r-sm: calc(var(--radius, 12px) - 8px);
            --lp-display: var(--lp-font-display, var(--lp-font, 'Hind Siliguri', 'Noto Sans Bengali', 'Nirmala UI', 'Vrinda', 'Shonar Bangla', 'Bangla Sangam MN', system-ui, sans-serif));
            --lp-ink: var(--primary-darker, #270361);
            --lp-muted: #6b5b7b;
            --lp-shadow: 0 10px 30px rgba(var(--primary-rgb, 79, 14, 148), .10);
            --lp-shadow-sm: 0 4px 14px rgba(var(--primary-rgb, 79, 14, 148), .08);

            /* Should Google Fonts not arrive — a slow ad click, a blocked
               domain — the fallbacks are the Bengali faces Android, Windows
               and Apple already ship, never a Latin font drawing Bengali. */
            font-family: var(--lp-font, 'Hind Siliguri', 'Noto Sans Bengali', 'Nirmala UI', 'Vrinda', 'Shonar Bangla', 'Bangla Sangam MN', system-ui, sans-serif);
            color: var(--dark, #1e1029);
            /* A faint polka dot, the wallpaper of a nursery. Cards sit on it in
               white, so the copy is never read over the pattern. */
            background-color: var(--cream, #fdfaff);
            background-image: radial-gradient(rgba(var(--primary-rgb, 79, 14, 148), .07) 1.6px, transparent 1.7px);
            background-size: 24px 24px;
            font-size: 1rem;
            line-height: 1.7;
            padding-bottom: 0;
            overflow-x: hidden;
        }

        /* Room for the sticky bar, only on the pages that have one. */
        body.has-sticky { padding-bottom: 90px; }

        /* Buttons and fields do not inherit the page's font on their own. */
        button, input, select, textarea { font-family: inherit; }

        img { max-width: 100%; height: auto; display: block; }
        a { color: var(--primary, #4f0e94); }
        [hidden] { display: none !important; }

        /* One column at every width. .lp-wrap is the measure and it is the only
           thing that changes between a phone and a desktop — the page is never
           rearranged, only resized, so there is one layout to get right. */
        .lp-shell { width: 100%; }
        .lp-wrap { max-width: 620px; margin: 0 auto; padding: 0 16px; }

        /* The sticky header would otherwise sit on top of whatever an
           in-page link jumps to. */
        [id] { scroll-margin-top: 76px; }

        /* ---------------------------------------------------------- header */

        .lp-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 20;
            box-shadow: var(--lp-shadow-sm);
        }
        /* A crayon stripe under the logo: the first thing that says "toys". */
        .lp-header::after {
            content: '';
            position: absolute;
            left: 0; right: 0; bottom: -4px;
            height: 4px;
            background: var(--lp-rainbow, var(--primary, #4f0e94));
        }
        .lp-header .brand-logo { height: 34px; width: auto; }
        .lp-call {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: var(--lp-display);
            font-weight: 700;
            font-size: .95rem;
            color: var(--accent-text, #7a0bb8);
            background: var(--kid-pink-tint, #f5ecff);
            text-decoration: none;
            border-radius: 999px;
            padding: 6px 14px;
            white-space: nowrap;
        }

        /* ----------------------------------------------------------- blocks */

        /* Block padding only: the side gutter belongs to .lp-wrap, which sits on
           the same element. */
        .lp-section { padding-block: 22px; }

        .lp-h1 {
            font-family: var(--lp-display);
            font-size: clamp(1.7rem, 7vw, 2.3rem);
            line-height: 1.25;
            font-weight: 800;
            margin: 0 0 8px;
            color: var(--lp-ink);
        }
        .lp-sub { font-size: 1.05rem; color: var(--lp-muted); margin: 0 0 14px; }

        /* Section headings carry a short crayon underline instead of a rule
           between sections — the cards already say where one ends. */
        .lp-h2 {
            font-family: var(--lp-display);
            font-size: 1.4rem;
            line-height: 1.3;
            font-weight: 800;
            margin: 0 0 16px;
            color: var(--lp-ink);
        }
        .lp-h2::after {
            content: '';
            display: block;
            width: 64px;
            height: 6px;
            margin-top: 6px;
            border-radius: 999px;
            background: var(--lp-rainbow, var(--accent, #bd06f1));
        }

        .lp-badge {
            display: inline-block;
            background: var(--accent, #bd06f1);
            color: var(--lp-badge-text, #fff);
            font-family: var(--lp-display);
            font-weight: 700;
            font-size: .95rem;
            line-height: 1.4;
            border-radius: 999px;
            padding: 6px 18px;
            margin-bottom: 14px;
            transform: rotate(-2deg);
            box-shadow: 0 4px 0 var(--accent-text, #7a0bb8);
        }

        /* --------------------------------------------------------- the hero */

        /* Full width, not .lp-wrap width — the band is the theme announcing
           itself and a 620px rectangle of it floating in cream reads as a
           mistake. */
        .lp-hero-band {
            position: relative;
            overflow: hidden;
            background: var(--lp-hero-band, transparent);
        }
        .lp-hero {
            position: relative;
            z-index: 1;
            text-align: center;
            padding-top: 30px;
            padding-bottom: 10px;
        }

        /* A wavy hem where the band meets the page. */
        .lp-wave { display: block; width: 100%; height: 34px; margin-top: -1px; }
        .lp-wave path { fill: var(--cream, #fdfaff); }

        /*
           Clouds, balloons and stars drifting round the headline. Pure CSS, so
           they cost no request; aria-hidden, so a screen reader skips them;
           kept to the edges and behind the text, so they never sit on a word.
        */
        .lp-deco { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
        .lp-deco span { position: absolute; display: block; }

        .lp-cloud {
            width: 74px; height: 24px;
            background: #fff;
            border-radius: 999px;
            opacity: .9;
        }
        .lp-cloud::before, .lp-cloud::after {
            content: '';
            position: absolute;
            background: #fff;
            border-radius: 50%;
        }
        .lp-cloud::before { width: 32px; height: 32px; left: 12px; top: -16px; }
        .lp-cloud::after { width: 24px; height: 24px; left: 38px; top: -10px; }
        .lp-cloud-1 { top: 22px; left: -14px; animation: lp-drift 14s ease-in-out infinite alternate; }
        .lp-cloud-2 { top: 64%; right: -18px; transform: scale(.8); animation: lp-drift 18s ease-in-out infinite alternate-reverse; }

        .lp-balloon {
            width: 34px; height: 42px;
            border-radius: 50% 50% 48% 48% / 56% 56% 44% 44%;
            box-shadow: inset -6px -6px 0 rgba(0, 0, 0, .06);
            animation: lp-float 6s ease-in-out infinite;
        }
        /* The knot and the string. */
        .lp-balloon::before {
            content: '';
            position: absolute;
            left: 50%; bottom: -5px;
            border: 5px solid transparent;
            border-bottom-color: inherit;
            transform: translateX(-50%) rotate(180deg);
        }
        .lp-balloon::after {
            content: '';
            position: absolute;
            left: 50%; top: 100%;
            width: 1.5px; height: 40px;
            margin-top: 5px;
            background: rgba(var(--primary-rgb, 79, 14, 148), .3);
        }
        .lp-balloon-1 { top: 70px; right: 6%; background: var(--kid-pink, #f9a8d4); border-color: var(--kid-pink, #f9a8d4); }
        .lp-balloon-2 { top: 110px; right: calc(6% + 30px); width: 26px; height: 33px; background: var(--kid-sky, #7dd3fc); border-color: var(--kid-sky, #7dd3fc); animation-delay: -2s; }
        .lp-balloon-3 { bottom: 40px; left: 4%; width: 28px; height: 35px; background: var(--kid-yellow, #fcd34d); border-color: var(--kid-yellow, #fcd34d); animation-delay: -4s; }

        .lp-star {
            width: 22px; height: 22px;
            background: var(--kid-yellow, #fcd34d);
            clip-path: polygon(50% 0, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
            animation: lp-twinkle 3.2s ease-in-out infinite;
        }
        .lp-star-1 { top: 18px; right: 28%; }
        .lp-star-2 { top: 46%; left: 7%; width: 16px; height: 16px; background: var(--kid-lilac, #c4b5fd); animation-delay: -1.2s; }
        .lp-star-3 { bottom: 26px; right: 18%; width: 18px; height: 18px; background: var(--kid-orange, #fdba74); animation-delay: -2.1s; }

        .lp-dot { width: 12px; height: 12px; border-radius: 50%; animation: lp-float 7s ease-in-out infinite; }
        .lp-dot-1 { top: 30%; left: 16%; background: var(--kid-mint, #6ee7b7); }
        .lp-dot-2 { top: 14%; left: 40%; width: 8px; height: 8px; background: var(--kid-pink, #f9a8d4); animation-delay: -3s; }
        .lp-dot-3 { bottom: 34%; right: 4%; width: 10px; height: 10px; background: var(--kid-lilac, #c4b5fd); animation-delay: -5s; }

        @keyframes lp-float {
            0%, 100% { transform: translateY(0) rotate(-3deg); }
            50% { transform: translateY(-10px) rotate(3deg); }
        }
        @keyframes lp-drift {
            from { translate: 0 0; }
            to { translate: 28px 0; }
        }
        @keyframes lp-twinkle {
            0%, 100% { transform: scale(1) rotate(0); opacity: 1; }
            50% { transform: scale(.75) rotate(20deg); opacity: .7; }
        }

        /* What the shop promises on every order, under the price. */
        .lp-perks {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin: 16px 0 4px;
            padding: 0;
            list-style: none;
        }
        .lp-perks li {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff;
            border-radius: 999px;
            padding: 5px 13px;
            font-size: .88rem;
            font-weight: 600;
            color: var(--lp-ink);
            box-shadow: var(--lp-shadow-sm);
        }

        .lp-hero-media {
            border-radius: var(--lp-r-lg);
            overflow: hidden;
            border: 5px solid #fff;
            background: #fff;
            box-shadow: var(--lp-shadow), 8px 10px 0 var(--kid-yellow, var(--cream-dark, #f5ecff));
        }
        .lp-hero-media iframe { width: 100%; aspect-ratio: 16/9; border: 0; display: block; }
        .lp-hero-media.is-upright { max-width: 340px; margin-left: auto; margin-right: auto; }
        .lp-hero-media.is-upright iframe { aspect-ratio: 9/16; }
        .lp-media-section { padding-top: 4px; }

        /* The offer and the order form: the two panels the page exists for. */
        .lp-offer {
            position: relative;
            background: #fff;
            border-radius: var(--lp-r-lg);
            padding: 26px 16px 20px;
            box-shadow: var(--lp-shadow);
            overflow: hidden;
        }
        .lp-offer::before {
            content: '';
            position: absolute;
            left: 0; right: 0; top: 0;
            height: 7px;
            background: var(--lp-rainbow, var(--primary, #4f0e94));
        }

        .lp-card {
            background: #fff;
            border-radius: var(--lp-r);
            padding: 16px;
            box-shadow: var(--lp-shadow-sm);
        }
        .lp-offer .lp-card { box-shadow: none; border: 2px dashed var(--cream-dark, #f5ecff); }

        /* ----------------------------------------------------------- price */

        /* The price sits in a bubble of its own, with a sticker for the saving. */
        .lp-price {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 4px 12px;
            background: #fff;
            border-radius: var(--lp-r);
            padding: 6px 20px;
            margin: 6px 0 4px;
            box-shadow: 0 5px 0 var(--kid-yellow, var(--cream-dark, #f5ecff)), var(--lp-shadow);
        }
        .lp-price-label { font-size: .9rem; color: var(--lp-muted); }
        .lp-price-now {
            font-family: var(--lp-display);
            font-size: 2.3rem;
            line-height: 1.3;
            font-weight: 800;
            color: var(--accent-text, var(--primary, #4f0e94));
        }
        .lp-price-was { font-size: 1.1rem; color: #9186a0; text-decoration: line-through; }
        .lp-save {
            background: var(--kid-yellow, #f5e0ff);
            color: var(--lp-ink);
            font-family: var(--lp-display);
            font-weight: 700;
            font-size: .9rem;
            border-radius: 999px;
            padding: 2px 12px;
            transform: rotate(-4deg);
        }

        /* --------------------------------------------------------- packages */

        .lp-pack {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 2.5px solid var(--cream-dark, #f5ecff);
            border-radius: var(--lp-r);
            padding: 10px 12px;
            margin-bottom: 12px;
            background: #fff;
            cursor: pointer;
            transition: border-color .15s, box-shadow .15s, transform .15s;
        }
        .lp-pack:hover { transform: translateY(-1px); }
        .lp-pack:has(input:checked) {
            border-color: var(--accent, #bd06f1);
            background: var(--kid-pink-tint, #f8f0ff);
            box-shadow: 0 4px 0 var(--accent-gold, #e6b3ff);
        }
        .lp-pack input { width: 22px; height: 22px; accent-color: var(--accent, #4f0e94); flex: none; }
        .lp-pack img {
            width: 60px; height: 60px;
            object-fit: cover;
            border-radius: var(--lp-r-sm);
            flex: none;
            background: var(--kid-sky-tint, #f5ecff);
        }
        .lp-pack-body { flex: 1 1 auto; min-width: 0; }
        /* A long Bengali product name must wrap rather than push the price off
           the edge of a 320px screen. */
        .lp-pack-name { font-weight: 600; line-height: 1.4; overflow-wrap: anywhere; }
        .lp-pack-price {
            font-family: var(--lp-display);
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--accent-text, #4f0e94);
            white-space: nowrap;
        }
        .lp-pack-was { color: #9186a0; text-decoration: line-through; font-size: .85rem; margin-left: 6px; }
        .lp-pack-meta { font-size: .85rem; color: var(--lp-muted); }
        .lp-pack-out { font-size: .85rem; color: #a52117; font-weight: 600; }
        .lp-pack.is-out { opacity: .55; cursor: not-allowed; transform: none; }
        .lp-pack-static { cursor: default; }
        .lp-pack-static:hover { transform: none; }

        /* Several products, each with its own "এটা নিতে চাই" / − n + picker. */
        .lp-pick-hint { margin: -6px 0 16px; font-size: .92rem; color: var(--lp-muted); }
        .lp-pick { align-items: flex-start; cursor: default; }
        .lp-pick:hover { transform: none; }
        .lp-pick.is-picked {
            border-color: var(--accent, #bd06f1);
            background: var(--kid-pink-tint, #f8f0ff);
            box-shadow: 0 4px 0 var(--accent-gold, #e6b3ff);
        }
        .lp-pick.is-picked::after {
            content: '✓';
            position: absolute;
            top: -10px; right: -8px;
            width: 28px; height: 28px;
            border-radius: 50%;
            background: var(--accent, #bd06f1);
            color: #fff;
            font-weight: 800;
            line-height: 28px;
            text-align: center;
            box-shadow: 0 2px 0 var(--accent-text, #7a0bb8);
        }
        .lp-pick-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px 12px;
            margin-top: 6px;
        }
        .lp-add {
            display: none;
            border: 0;
            border-radius: 999px;
            background: var(--primary, #4f0e94);
            color: #fff;
            font-family: var(--lp-display);
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.3;
            padding: 8px 18px;
            cursor: pointer;
            box-shadow: 0 3px 0 var(--primary-darker, #270361);
        }
        .lp-add:active { transform: translateY(2px); box-shadow: 0 1px 0 var(--primary-darker, #270361); }
        .lp-step {
            display: inline-flex;
            align-items: center;
            background: #fff;
            border: 2px solid var(--accent, #bd06f1);
            border-radius: 999px;
            overflow: hidden;
        }
        .lp-step button {
            display: none;
            width: 40px; height: 40px;
            border: 0;
            background: var(--accent, #bd06f1);
            color: #fff;
            font-size: 1.35rem;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
        }
        .lp-step button:disabled { opacity: .35; cursor: not-allowed; }
        .lp-step input {
            width: 48px;
            border: 0;
            background: transparent;
            text-align: center;
            font-family: inherit;
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--lp-ink);
            padding: 7px 0;
            -moz-appearance: textfield;
            appearance: textfield;
        }
        .lp-step input::-webkit-inner-spin-button,
        .lp-step input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        .lp-step input:focus { outline: none; }
        .lp-step:focus-within { box-shadow: 0 0 0 4px var(--kid-pink-tint, rgba(189, 6, 241, .15)); }
        /* With the script running: the buttons appear, and a product not yet
           picked shows only its "এটা নিতে চাই". Without it, the number box alone. */
        .lp-js .lp-step button { display: block; }
        .lp-js .lp-pick:not(.is-picked) .lp-add { display: inline-block; }
        .lp-js .lp-pick:not(.is-picked) .lp-step { display: none; }
        .lp-pick-summary {
            margin-top: 4px;
            padding: 9px 14px;
            border-radius: var(--lp-r-sm);
            background: var(--kid-mint-tint, #f3e8ff);
            color: #065f46;
            font-weight: 700;
            text-align: center;
        }
        .lp-pick-summary.is-empty { background: var(--kid-orange-tint, #fff6e5); color: #7a4c00; }

        .lp-qty { display: flex; align-items: center; gap: 10px; margin-top: 12px; font-weight: 600; }
        .lp-qty select, .lp-pack select, .lp-field input, .lp-field select, .lp-field textarea {
            font-family: inherit;
            font-size: 1rem;
            border: 2px solid var(--cream-dark, #dee2e6);
            border-radius: var(--lp-r-sm);
            padding: 11px 14px;
            width: 100%;
            background: #fff;
            color: inherit;
            transition: border-color .15s, box-shadow .15s;
        }
        .lp-qty select, .lp-pack select { width: auto; padding-right: 30px; font-weight: 700; }
        .lp-qty select:focus, .lp-pack select:focus,
        .lp-field input:focus, .lp-field select:focus, .lp-field textarea:focus {
            outline: none;
            border-color: var(--accent, #9a41f0);
            box-shadow: 0 0 0 4px var(--kid-pink-tint, rgba(var(--primary-rgb, 79,14,148), .15));
        }

        /* ------------------------------------------------------------ lists */

        /* Each reason to buy is its own crayon-coloured card, the five colours
           taking turns so a long list still reads as a set of separate things. */
        .lp-features {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 12px;
        }
        .lp-features li {
            --c: var(--kid-yellow, var(--primary-light, #9a41f0));
            --t: var(--kid-yellow-tint, var(--cream-dark, #f5ecff));
            position: relative;
            display: flex;
            align-items: center;
            min-height: 58px;
            padding: 12px 14px 12px 58px;
            border-radius: var(--lp-r);
            background: var(--t);
            font-weight: 600;
            line-height: 1.5;
        }
        .lp-features li:nth-child(5n+2) { --c: var(--kid-sky, #9a41f0); --t: var(--kid-sky-tint, #f5ecff); }
        .lp-features li:nth-child(5n+3) { --c: var(--kid-mint, #9a41f0); --t: var(--kid-mint-tint, #f5ecff); }
        .lp-features li:nth-child(5n+4) { --c: var(--kid-pink, #9a41f0); --t: var(--kid-pink-tint, #f5ecff); }
        .lp-features li:nth-child(5n+5) { --c: var(--kid-lilac, #9a41f0); --t: var(--kid-lilac-tint, #f5ecff); }
        .lp-features li::before {
            content: var(--lp-tick, '✓');
            position: absolute;
            left: 12px;
            top: 50%;
            width: 34px;
            height: 34px;
            margin-top: -17px;
            line-height: 34px;
            text-align: center;
            border-radius: 50%;
            background: var(--c);
            color: var(--lp-ink);
            font-size: 1rem;
            font-weight: 700;
            box-shadow: 0 3px 0 rgba(0, 0, 0, .08);
        }

        /* ----------------------------------------------------------- specs */

        /* Two columns on every screen: a spec table that reflows to one column
           on a phone stops being a table and becomes a list you have to read. */
        .lp-specs {
            margin: 0;
            background: #fff;
            border-radius: var(--lp-r);
            overflow: hidden;
            box-shadow: var(--lp-shadow-sm);
        }
        .lp-spec { display: flex; gap: 12px; padding: 11px 16px; }
        .lp-spec:nth-child(odd) { background: var(--kid-lilac-tint, var(--cream-dark, #f5ecff)); }
        .lp-spec dt { flex: 0 0 40%; font-weight: 600; color: var(--lp-muted); }
        .lp-spec dd {
            flex: 1 1 auto;
            margin: 0;
            font-weight: 600;
            /* A model number has no spaces and must not widen the page. */
            overflow-wrap: anywhere;
        }

        /* --------------------------------------------------------- gallery */

        .lp-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 14px;
        }
        /* Snapshots pinned to a board, each a little askew. */
        .lp-gallery img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border: 5px solid #fff;
            border-radius: var(--lp-r);
            box-shadow: var(--lp-shadow-sm);
            transform: rotate(-1.5deg);
            transition: transform .2s;
        }
        .lp-gallery img:nth-child(even) { transform: rotate(1.5deg); }
        .lp-gallery img:hover { transform: rotate(0) scale(1.03); }

        /* --------------------------------------------------------- reviews */

        .lp-reviews { display: grid; gap: 16px; }
        .lp-review {
            --c: var(--kid-sky, #e6b3ff);
            position: relative;
            margin: 0;
            background: #fff;
            border-radius: var(--lp-r);
            padding: 14px 16px;
            border: 2.5px solid var(--c);
            box-shadow: var(--lp-shadow-sm);
        }
        .lp-review:nth-child(4n+2) { --c: var(--kid-pink, #e6b3ff); }
        .lp-review:nth-child(4n+3) { --c: var(--kid-mint, #e6b3ff); }
        .lp-review:nth-child(4n+4) { --c: var(--kid-yellow, #e6b3ff); }
        .lp-review blockquote { margin: 4px 0 10px; }
        .lp-review-name {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: .95rem;
        }
        .lp-avatar {
            width: 34px; height: 34px;
            flex: none;
            border-radius: 50%;
            background: var(--c);
            color: var(--lp-ink);
            font-family: var(--lp-display);
            font-weight: 800;
            line-height: 34px;
            text-align: center;
        }
        .lp-stars { color: #f59e0b; font-size: 1.05rem; letter-spacing: 2px; }

        /* ------------------------------------------------------------- faqs */

        .lp-faq {
            background: #fff;
            border-radius: var(--lp-r);
            padding: 0 16px;
            margin-bottom: 10px;
            box-shadow: var(--lp-shadow-sm);
            border: 2px solid transparent;
            transition: border-color .15s;
        }
        .lp-faq[open] { border-color: var(--kid-lilac, var(--cream-dark, #f5ecff)); }
        .lp-faq summary {
            cursor: pointer;
            font-weight: 600;
            padding: 14px 0;
            list-style: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .lp-faq summary::-webkit-details-marker { display: none; }
        .lp-faq summary::after {
            content: '+';
            flex: none;
            width: 28px; height: 28px;
            line-height: 28px;
            text-align: center;
            border-radius: 50%;
            background: var(--accent, #4f0e94);
            color: #fff;
            font-weight: 700;
        }
        .lp-faq[open] summary::after { content: '−'; background: var(--primary, #4f0e94); }
        .lp-faq p { margin: 0 0 14px; color: var(--lp-muted); }

        /* This block is whatever the admin pasted into the editor, so it is the
           one place a stray table or a wide embed can push the page sideways. */
        .lp-body-copy {
            overflow-x: auto;
            background: #fff;
            border-radius: var(--lp-r);
            padding: 18px 16px;
            box-shadow: var(--lp-shadow-sm);
        }
        .lp-body-copy > :first-child { margin-top: 0; }
        .lp-body-copy > :last-child { margin-bottom: 0; }
        .lp-body-copy img { border-radius: var(--lp-r-sm); margin: 10px 0; }
        .lp-body-copy h2, .lp-body-copy h3 { font-family: var(--lp-display); color: var(--lp-ink); }
        .lp-body-copy table { width: 100%; border-collapse: collapse; }
        .lp-body-copy iframe, .lp-body-copy video { max-width: 100%; }

        /* --------------------------------------------------------- delivery */

        .lp-info-row { display: flex; gap: 12px; align-items: flex-start; }
        .lp-info-icon {
            width: 42px; height: 42px;
            flex: none;
            border-radius: 50%;
            background: var(--kid-sky-tint, var(--cream-dark, #f5ecff));
            font-size: 1.25rem;
            line-height: 42px;
            text-align: center;
        }
        .lp-info-row + .lp-info-row { margin-top: 14px; padding-top: 14px; border-top: 2px dashed var(--cream-dark, #f5ecff); }
        .lp-info-row .lp-info-icon-pay { background: var(--kid-mint-tint, var(--cream-dark, #f5ecff)); }
        .lp-info-body { flex: 1 1 auto; min-width: 0; }
        .lp-info-title { font-weight: 700; color: var(--lp-ink); }
        .lp-info-note { margin: 6px 0 0; font-size: .92rem; color: var(--lp-muted); white-space: pre-line; }
        .lp-info-sub { margin: 8px 0 2px; font-size: .88rem; color: var(--lp-muted); }
        .lp-free-tag {
            display: inline-block;
            background: var(--kid-mint-tint, #d1fae5);
            color: #047857;
            border-radius: 999px;
            padding: 0 10px;
            white-space: nowrap;
        }

        /* ----------------------------------------------------------- forms */

        .lp-field { margin-bottom: 14px; }
        .lp-field label { display: block; font-weight: 600; font-size: .95rem; margin-bottom: 6px; }
        .lp-field .lp-error { color: #c62828; font-size: .86rem; margin-top: 4px; }
        .lp-field input.is-bad, .lp-field select.is-bad, .lp-field textarea.is-bad { border-color: #c62828; }
        .lp-req { color: #c62828; }

        /* A chunky toy-box button: it sits on its own shadow and presses down. */
        .lp-btn {
            display: block;
            width: 100%;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--accent, #bd06f1) 0%, var(--primary, #4f0e94) 100%);
            color: #fff;
            font-family: var(--lp-display);
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.3;
            padding: 15px 22px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 5px 0 var(--primary-darker, #270361), 0 12px 24px rgba(var(--primary-rgb, 79, 14, 148), .25);
            transition: transform .1s, box-shadow .1s, filter .15s;
        }
        .lp-btn:hover { filter: brightness(1.06); }
        .lp-btn:active { transform: translateY(3px); box-shadow: 0 2px 0 var(--primary-darker, #270361), 0 6px 14px rgba(var(--primary-rgb, 79, 14, 148), .2); }
        .lp-btn:disabled { background: #b9aec4; box-shadow: none; cursor: not-allowed; transform: none; filter: none; }
        .lp-btn-jump { margin-bottom: 30px; }
        .lp-btn-whatsapp { background: #25d366; box-shadow: 0 5px 0 #128c4b; margin-top: 8px; }
        .lp-btn-ghost {
            background: #fff;
            color: var(--primary, #4f0e94);
            border: 2.5px solid var(--primary, #4f0e94);
            box-shadow: 0 5px 0 var(--cream-dark, #f5ecff);
            margin-top: 14px;
        }

        .lp-total {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 5px 0;
            font-size: .96rem;
        }
        .lp-total.is-grand {
            border-top: 2px dashed var(--cream-dark, #dee2e6);
            margin-top: 6px;
            padding-top: 10px;
            font-family: var(--lp-display);
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--accent-text, #3a0775);
        }
        .lp-form-note { text-align: center; font-size: .88rem; color: var(--lp-muted); margin: 14px 0 0; }

        .lp-alert {
            border-radius: var(--lp-r-sm);
            padding: 12px 14px;
            margin-bottom: 14px;
            font-weight: 600;
        }
        .lp-alert-bad { background: #fdecea; color: #a52117; }
        .lp-alert-note { background: var(--kid-orange-tint, #fff6e5); color: #7a4c00; }
        .lp-alert-info { background: var(--kid-mint-tint, #f3e8ff); color: #065f46; }

        /* ------------------------------------------------------ sticky bar */

        .lp-sticky {
            position: fixed;
            left: 0; right: 0; bottom: 0;
            background: #fff;
            border-radius: var(--lp-r) var(--lp-r) 0 0;
            box-shadow: 0 -6px 24px rgba(var(--primary-rgb, 79, 14, 148), .14);
            padding: 10px 16px calc(12px + env(safe-area-inset-bottom));
            z-index: 30;
        }
        .lp-sticky .lp-wrap { display: flex; align-items: center; gap: 14px; padding: 0; }
        .lp-sticky-label { font-size: .75rem; color: var(--lp-muted); line-height: 1; }
        .lp-sticky-price {
            font-family: var(--lp-display);
            font-weight: 800;
            font-size: 1.3rem;
            line-height: 1.3;
            color: var(--accent-text, #3a0775);
            white-space: nowrap;
        }
        .lp-sticky .lp-btn {
            width: auto;
            flex: 1 1 auto;
            font-size: 1.05rem;
            padding: 11px 16px;
            animation: lp-nudge 3s ease-in-out infinite;
        }
        /* A small hop now and then, the way a toy on a shelf catches the eye. */
        @keyframes lp-nudge {
            0%, 82%, 100% { transform: translateY(0); }
            88% { transform: translateY(-4px); }
            94% { transform: translateY(0); }
        }

        .lp-foot {
            text-align: center;
            font-size: .88rem;
            color: var(--lp-muted);
            padding: 26px 16px 10px;
        }

        .lp-preview {
            background: #7a4c00;
            color: #fff;
            text-align: center;
            font-weight: 700;
            font-size: .9rem;
            padding: 7px 16px;
        }

        /* ------------------------------------------------------- thank you */

        .lp-done { text-align: center; margin-bottom: 22px; position: relative; }
        .lp-done-mark {
            width: 84px; height: 84px;
            margin: 6px auto 14px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent, #bd06f1), var(--primary, #4f0e94));
            color: #fff;
            font-size: 2.6rem;
            line-height: 84px;
            box-shadow: 0 6px 0 var(--primary-darker, #270361);
            animation: lp-pop .6s cubic-bezier(.2, 1.6, .4, 1) both;
        }
        @keyframes lp-pop {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
        /* A one-off shower of paper bits over the tick. */
        .lp-confetti { position: absolute; inset: -20px 0 0; pointer-events: none; overflow: hidden; height: 180px; }
        .lp-confetti span {
            position: absolute;
            top: -12px;
            width: 9px; height: 14px;
            border-radius: 2px;
            opacity: 0;
            animation: lp-fall 2.4s ease-in forwards;
        }
        .lp-confetti span:nth-child(6n+1) { background: var(--kid-yellow, #fcd34d); }
        .lp-confetti span:nth-child(6n+2) { background: var(--kid-pink, #f9a8d4); }
        .lp-confetti span:nth-child(6n+3) { background: var(--kid-sky, #7dd3fc); }
        .lp-confetti span:nth-child(6n+4) { background: var(--kid-mint, #6ee7b7); }
        .lp-confetti span:nth-child(6n+5) { background: var(--kid-lilac, #c4b5fd); }
        .lp-confetti span:nth-child(6n+6) { background: var(--kid-orange, #fdba74); }
        @keyframes lp-fall {
            0% { opacity: 1; transform: translateY(0) rotate(0); }
            100% { opacity: 0; transform: translateY(190px) rotate(540deg); }
        }

        /*
           Small phones. The hero picture runs nearly edge to edge — a 16px
           gutter round the one picture that has to sell the product wastes the
           width it needed — but keeps its rounded frame.
        */
        @media (max-width: 575.98px) {
            .lp-hero-media { margin-left: -8px; margin-right: -8px; border-width: 4px; }
            /* A narrow headline fills the width, so the decorations go to the
               corners and the ones that would land on words are dropped. */
            .lp-cloud-1 { top: 8px; }
            .lp-balloon-1 { top: 8px; right: 3%; width: 26px; height: 33px; }
            .lp-balloon-1::after { height: 26px; }
            .lp-deco .lp-balloon-2, .lp-deco .lp-dot, .lp-deco .lp-star-2 { display: none; }
            .lp-balloon-3 { bottom: 6px; left: 3%; width: 22px; height: 28px; }
            .lp-balloon-3::after { height: 20px; }
            .lp-star-1 { top: 12px; right: 22%; width: 16px; height: 16px; }
            .lp-star-3 { bottom: 14px; }
        }

        @media (min-width: 576px) {
            body { font-size: 1.05rem; }
            .lp-section { padding-block: 28px; }
            .lp-offer { padding: 30px 24px 24px; }
            .lp-features { grid-template-columns: 1fr 1fr; }
            .lp-reviews { grid-template-columns: 1fr 1fr; }
        }

        /*
           Desktop is the same page, wider and larger — not a different one.
           620px of body text on a 1080p monitor reads as a leftover phone
           screen, and a second column would put the order form somewhere the
           eye does not go next.
        */
        @media (min-width: 992px) {
            body { font-size: 1.1rem; }
            body.has-sticky { padding-bottom: 96px; }

            .lp-wrap { max-width: 820px; padding: 0 24px; }
            .lp-section { padding-block: 36px; }
            .lp-hero { padding-top: 48px; }
            .lp-offer { padding: 38px 34px 32px; }

            .lp-h1 { font-size: 3rem; }
            .lp-sub { font-size: 1.25rem; }
            .lp-h2 { font-size: 1.75rem; }
            .lp-price-now { font-size: 2.8rem; }
            .lp-price-was { font-size: 1.3rem; }
            .lp-save { font-size: 1rem; }
            .lp-wave { height: 54px; }

            .lp-cloud-1 { left: 6%; top: 40px; transform: scale(1.4); }
            .lp-cloud-2 { right: 8%; transform: scale(1.2); }
            .lp-balloon-1 { right: 12%; top: 90px; width: 44px; height: 55px; }
            .lp-balloon-2 { right: calc(12% + 46px); top: 140px; }
            .lp-balloon-3 { left: 10%; }
            .lp-star-2 { left: 14%; }

            .lp-pack { padding: 14px 16px; gap: 16px; }
            .lp-pack img { width: 72px; height: 72px; }
            .lp-header { padding: 12px 24px; }
            .lp-header .brand-logo { height: 40px; }
        }

        /* Everything that moves is decoration, so all of it can stop. */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
            html { scroll-behavior: auto; }
            .lp-confetti { display: none; }
        }
    </style>

    @vite(['resources/js/landing.js'])
    @stack('styles')
</head>
{{-- The theme is a class on <body> and nothing else: custom properties inherit,
     and the nearest ancestor that defines one wins, so this beats brand.css's
     :root for everything inside without a specificity fight. --}}
<body class="lp-theme-{{ $page->themeKey() }} {{ ($takingOrders ?? false) ? 'has-sticky' : '' }}">
    @if($preview ?? false)
        <div class="lp-preview">প্রিভিউ — এই পেজটি এখনো লাইভ নয়, শুধু আপনি দেখতে পাচ্ছেন।</div>
    @endif

    <header class="lp-header">
        <a href="{{ route('home') }}" aria-label="{{ \App\Models\Setting::get('site_title', 'BaburhashiBD') }}">
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
        © {{ \App\Support\Bangla::digits(date('Y')) }} {{ \App\Models\Setting::get('site_title', 'BaburhashiBD') }} ·
        <a href="{{ route('home') }}">মূল ওয়েবসাইট</a>
    </footer>

    @yield('sticky')
    @stack('scripts')
</body>
</html>
