@extends('layouts.landing')

@php
    $items = $page->items;
    $defaultItem = $items->firstWhere('is_default', true) ?? $items->first();
    $anyInStock = $items->contains(fn ($item) => $item->inStock());
    $takingOrders = $open && $anyInStock;

    // The JS below only redraws the running total; the server recalculates
    // everything again before an order is written. Keep the two in step, but
    // treat this copy as decoration.
    $config = [
        'mode' => $page->selection_mode,
        'bundleTotal' => $page->isBundle() ? $page->bundleTotal() : null,
        'delivery' => [
            'inside' => $page->deliveryChargeFor('dhaka_inside', 0),
            'outside' => $page->deliveryChargeFor('dhaka_outside', 0),
            // Above this the charge drops to zero. Asked of the same method
            // rather than re-derived, so the rule lives in exactly one place;
            // 0 means the charge never drops.
            'freeOver' => $page->deliveryChargeFor('dhaka_inside', PHP_INT_MAX) == 0.0
                ? (float) \App\Models\Setting::get('free_delivery_threshold', 2000)
                : 0.0,
        ],
    ];
@endphp

@section('content')
{{--
    One column at every width — a phone and a desktop see the same page in the
    same order, only wider and larger. The whole thing is inside one <form>, so
    the packages can sit high where the decision is made and the customer's
    details at the bottom after the reasons to buy.
--}}
<main class="lp-shell">
    <form id="lp-order" method="POST" action="{{ route('landing.order', $page->slug) }}" novalidate>
        @csrf

        @foreach(\App\Support\CampaignTracking::FIELDS as $field)
            @if(! empty($tracking[$field]))
                <input type="hidden" name="{{ $field }}" value="{{ $tracking[$field] }}">
            @endif
        @endforeach

        {{--
            A box no human sees. Anything typed in it came from a script.
            Clipped to a pixel rather than pushed off to the left, so it can
            never affect the page's own width.
        --}}
        <div style="position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);"
             aria-hidden="true">
            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <script type="application/json" id="lp-config">@json($config)</script>

        {{-- --------------------------------------------- headline and price --}}
        <section class="lp-wrap lp-section lp-hero-band">
            @if($page->badge_text)
                <span class="lp-badge">{{ $page->badge_text }}</span>
            @endif

            <h1 class="lp-h1">{{ $page->headline }}</h1>

            @if($page->subheadline)
                <p class="lp-sub">{{ $page->subheadline }}</p>
            @endif

            @include('landing.blocks.price')

            @if($page->countdown_ends_at?->isFuture())
                <div class="lp-countdown" data-countdown="{{ $page->countdown_ends_at->toIso8601String() }}">
                    <div><b data-cd="d">০</b><span>দিন</span></div>
                    <div><b data-cd="h">০</b><span>ঘণ্টা</span></div>
                    <div><b data-cd="m">০</b><span>মিনিট</span></div>
                    <div><b data-cd="s">০</b><span>সেকেন্ড</span></div>
                </div>
            @endif
        </section>

        {{-- ------------------------------------------------- picture or video --}}
        @if($page->videoEmbedUrl() && $page->showsSection('video'))
            <section class="lp-wrap lp-section lp-media-section">
                <div class="lp-hero-media">
                    <iframe src="{{ $page->videoEmbedUrl() }}" title="{{ $page->headline }}"
                            loading="lazy" allowfullscreen
                            allow="accelerometer; autoplay; encrypted-media; picture-in-picture"></iframe>
                </div>
            </section>
        @elseif($page->heroImageUrl())
            <section class="lp-wrap lp-section lp-media-section">
                <div class="lp-hero-media">
                    {{-- The largest thing on the page and the first seen: never lazy. --}}
                    <img src="{{ $page->heroImageUrl() }}" alt="{{ $page->headline }}" fetchpriority="high">
                </div>
            </section>
        @endif

        @if(! $open)
            <section class="lp-wrap lp-section">
                <div class="lp-alert lp-alert-note">{{ $closedReason ?? 'এই অফারটি এখন বন্ধ আছে।' }}</div>
                <a class="lp-btn" href="{{ route('shop') }}">দোকানের সব পণ্য দেখুন</a>
            </section>
        @elseif(! $anyInStock)
            <section class="lp-wrap lp-section">
                <div class="lp-alert lp-alert-note">দুঃখিত, এই মুহূর্তে স্টক শেষ।</div>
                <a class="lp-btn" href="{{ route('shop') }}">দোকানের সব পণ্য দেখুন</a>
            </section>
        @endif

        {{-- ----------------------------------------------------- what to buy --}}
        <section class="lp-wrap lp-section">
            <div class="lp-offer">
                @include('landing.blocks.packages')
            </div>
        </section>

        {{-- ------------------------------------- blocks, in the admin's order --}}
        @foreach($page->enabledSections() as $block)
            @continue($block === 'video')
            @include('landing.blocks.'.$block)
        @endforeach

        {{-- --------------------------------------------------------- the ask --}}
        <section class="lp-wrap lp-section" id="lp-form">
            <div class="lp-offer">
                @include('landing.blocks.form')
            </div>
        </section>
    </form>
</main>
@endsection

@section('sticky')
    @if($takingOrders)
        <div class="lp-sticky">
            <div class="lp-wrap">
                <div>
                    <div class="lp-sticky-label">সর্বমোট</div>
                    <div class="lp-sticky-price" data-total-grand>৳{{ number_format($openingQuote['total']) }}</div>
                </div>
                <a class="lp-btn" href="#lp-form">{{ $page->ctaText() }}</a>
            </div>
        </div>
    @endif
@endsection
