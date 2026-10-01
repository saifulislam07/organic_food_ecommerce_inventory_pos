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
            // At or above this the charge drops to zero, per area because a
            // custom page may charge its own fee on one side of Dhaka and the
            // shop's on the other. 0 means the charge never drops.
            'freeOver' => [
                'inside' => $page->freeDeliveryThreshold('dhaka_inside'),
                'outside' => $page->freeDeliveryThreshold('dhaka_outside'),
            ],
        ],
    ];
@endphp

@section('content')
{{--
    One column at every width — a phone and a desktop see the same page in the
    same order, only wider and larger. The whole thing is inside one <form>, so
    the reasons to buy can come first and the order run — packages, delivery,
    the customer's details — follow together at the end.
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
        <div class="lp-hero-band">
            <div class="lp-deco" aria-hidden="true">
                <span class="lp-cloud lp-cloud-1"></span>
                <span class="lp-cloud lp-cloud-2"></span>
                <span class="lp-balloon lp-balloon-1"></span>
                <span class="lp-balloon lp-balloon-2"></span>
                <span class="lp-balloon lp-balloon-3"></span>
                <span class="lp-star lp-star-1"></span>
                <span class="lp-star lp-star-2"></span>
                <span class="lp-star lp-star-3"></span>
                <span class="lp-dot lp-dot-1"></span>
                <span class="lp-dot lp-dot-2"></span>
                <span class="lp-dot lp-dot-3"></span>
            </div>

            <section class="lp-wrap lp-section lp-hero">
                @if($page->badge_text)
                    <span class="lp-badge">{{ $page->badge_text }}</span>
                @endif

                <h1 class="lp-h1">{{ $page->headline }}</h1>

                @if($page->subheadline)
                    <p class="lp-sub">{{ $page->subheadline }}</p>
                @endif

                @include('landing.blocks.price')

                {{-- Only what this page actually promises: each chip is read
                     off the page's own delivery and payment settings. --}}
                @if($takingOrders)
                    <ul class="lp-perks">
                        @if($page->delivery_mode === 'free')
                            <li>🚚 ফ্রি হোম ডেলিভারি</li>
                        @elseif($page->freeDeliveryThreshold() > 0)
                            <li>🚚 {{ \App\Support\Bangla::money($page->freeDeliveryThreshold()) }}+ অর্ডারে সারা দেশে ফ্রি ডেলিভারি</li>
                        @else
                            <li>🚚 সারা দেশে হোম ডেলিভারি</li>
                        @endif
                        @if($page->payment_mode !== 'advance')
                            <li>💵 ক্যাশ অন ডেলিভারি</li>
                        @endif
                        <li>📞 ফোনে কনফার্ম করে পাঠানো হয়</li>
                    </ul>
                @endif
            </section>

            <svg class="lp-wave" viewBox="0 0 1440 54" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                <path d="M0,28 C120,6 240,6 360,24 C480,42 600,48 720,30 C840,12 960,4 1080,20 C1200,36 1320,46 1440,30 L1440,54 L0,54 Z"/>
            </svg>
        </div>

        {{-- ------------------------------------------------- picture or video --}}
        {{--
            The picture leads whenever there is one, and a video then gets its
            own block further down. Only a page with no picture lets the video
            take the top — an uploaded hero must never vanish behind a link.
        --}}
        @if($page->heroImageUrl())
            <section class="lp-wrap lp-section lp-media-section">
                <div class="lp-hero-media">
                    {{-- The largest thing on the page and the first seen: never lazy. --}}
                    <img src="{{ $page->heroImageUrl() }}" alt="{{ $page->headline }}" fetchpriority="high">
                </div>
            </section>
        @elseif($page->showsSection('video') && $page->videoEmbedUrl())
            @include('landing.blocks.video', ['inHero' => true])
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

        {{-- ------------------------------------- blocks, in the admin's order --}}
        {{-- Everything that persuades comes first; delivery is held back to sit
             inside the order run below. --}}
        @foreach($page->enabledSections() as $block)
            @continue($block === 'delivery')
            {{-- Without a picture the video already played in the hero. --}}
            @continue($block === 'video' && ! $page->heroImageUrl())
            @include('landing.blocks.'.$block)
        @endforeach

        {{--
            The order run, always in this order and never split up: choose what
            to buy, see what delivery and payment come to, fill in the details.
            Nothing is allowed between them, so a buyer who has started never
            scrolls past a review to find the next step.
        --}}
        <section class="lp-wrap lp-section" id="lp-buy">
            <div class="lp-offer">
                @include('landing.blocks.packages')
            </div>
        </section>

        @if($page->showsSection('delivery'))
            @include('landing.blocks.delivery')
        @endif

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
                    <div class="lp-sticky-price" data-total-grand>{{ \App\Support\Bangla::money($openingQuote['total']) }}</div>
                </div>
                {{-- To the start of the order run, not the form: what to buy comes first. --}}
                <a class="lp-btn" href="#lp-buy">{{ $page->ctaText() }}</a>
            </div>
        </div>
    @endif
@endsection
