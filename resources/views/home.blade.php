@extends('layouts.frontend')

@section('title', 'BaburhashiBD – শিশুদের প্রিয় সবকিছুর অনলাইন শপ')

@section('content')
    {{-- Quoted in the service strip and again in the promo tiles further down. --}}
    @php
        $threshold = \App\Models\Setting::get('free_delivery_threshold', 2000);

        // Section headings are editable in Settings > Storefront Text; an empty
        // box falls back to the wording the page shipped with.
        $t = fn (string $key, string $bn, string $en) => \App\Models\Setting::get($key)
            ?: (app()->getLocale() == 'bn' ? $bn : $en);
    @endphp

    {{--
        Hero — the marketplace arrangement: the category rail on the left, the
        slider filling the rest. One carousel panel per hero_slides row; a shop
        with no slides gets a single panel built from the site settings, so
        there is only one path through the markup either way.
    --}}
    <section class="pc-hero">
        <div class="container">
            <div class="pc-hero-grid">
                <aside class="pc-hero-rail">
                    <div class="pc-hero-rail-head">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                        {{ app()->getLocale() == 'bn' ? 'সব ক্যাটাগরি' : 'All Categories' }}
                    </div>
                    <div class="pc-hero-rail-body">
                        @include('partials.category-list', ['categories' => $categories])
                    </div>
                </aside>

                <div id="heroSlider" class="pc-hero-slider carousel slide"
                     data-bs-ride="carousel" data-bs-interval="6000" data-bs-pause="hover">
                    <div class="carousel-inner">
                        @foreach($slides as $slide)
                        <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                            <div class="pc-slide-media">
                                <img src="{{ $slide->image_url }}" alt="{{ strip_tags($slide->title) }}"
                                     loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                            </div>
                            <div class="pc-slide-body">
                                @if($slide->badge)
                                    <span class="pc-slide-badge">
                                        <i class="bi bi-patch-check-fill"></i> {{ $slide->badge }}
                                    </span>
                                @endif
                                {{-- Admin-authored, and allowed the <br> and <span> the styling needs. --}}
                                <h2 class="pc-slide-title">{!! $slide->title !!}</h2>
                                @if($slide->subtitle)
                                    <p class="pc-slide-text">{{ $slide->subtitle }}</p>
                                @endif
                                <a href="{{ $slide->button_link }}" class="pc-slide-btn">
                                    <i class="bi bi-bag-check"></i> {{ $slide->button_text }}
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- A single slide is not a slider: no arrows, no dots. --}}
                    @if($slides->count() > 1)
                        <button class="carousel-control-prev" type="button" data-bs-target="#heroSlider" data-bs-slide="prev">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                            <span class="visually-hidden">{{ app()->getLocale() == 'bn' ? 'আগেরটি' : 'Previous' }}</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#heroSlider" data-bs-slide="next">
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            <span class="visually-hidden">{{ app()->getLocale() == 'bn' ? 'পরেরটি' : 'Next' }}</span>
                        </button>
                        <div class="carousel-indicators">
                            @foreach($slides as $slide)
                                <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="{{ $loop->index }}"
                                        class="{{ $loop->first ? 'active' : '' }}"
                                        @if($loop->first) aria-current="true" @endif
                                        aria-label="{{ (app()->getLocale() == 'bn' ? 'স্লাইড ' : 'Slide ').$loop->iteration }}"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{--
                What the shop promises, in the strip right under the banner.
                Admin > Settings > Storefront Blocks > Service Strip. The two
                placeholders let a card quote the live figures rather than a
                number that has to be kept in step by hand.
            --}}
            @if($services->count())
            <div class="pc-services">
                <div class="pc-services-grid">
                    @foreach($services as $service)
                        <div class="pc-service">
                            <span class="pc-service-icon"><i class="bi bi-{{ $service->icon_name }}"></i></span>
                            <div>
                                <h4>{{ $service->title }}</h4>
                                @if($service->subtitle)
                                    <p>{{ strtr($service->subtitle, [
                                        ':threshold' => number_format($threshold),
                                        ':phone' => \App\Models\Setting::get('phone', '+880 1XXX-XXXXXX'),
                                    ]) }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </section>

    {{-- Shop by category --}}
    @if($categories->count())
    <section class="pc-section">
        <div class="container">
            <div class="pc-section-head">
                <h2><i class="bi bi-grid-3x3-gap-fill"></i> {{ $t('section_categories', 'ক্যাটাগরি অনুযায়ী কিনুন', 'Shop by Category') }}</h2>
                <a href="{{ route('shop') }}" class="pc-section-more">
                    {{ app()->getLocale() == 'bn' ? 'সব দেখুন' : 'View all' }} <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="pc-carousel-wrap">
                <div class="pc-carousel pc-cat-grid" data-carousel>
                    @foreach($categories as $category)
                        <a href="{{ route('shop', ['category' => $category->slug]) }}" class="pc-cat-tile">
                            <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="pc-cat-tile-img" loading="lazy">
                            <div class="pc-cat-tile-name">{{ $category->name }}</div>
                        </a>
                    @endforeach
                </div>
                @if($categories->count() > 6)
                    <button type="button" class="pc-carousel-nav pc-carousel-prev" data-carousel-prev aria-label="{{ app()->getLocale() == 'bn' ? 'আগেরটি' : 'Previous' }}"><i class="bi bi-chevron-left"></i></button>
                    <button type="button" class="pc-carousel-nav pc-carousel-next" data-carousel-next aria-label="{{ app()->getLocale() == 'bn' ? 'পরেরটি' : 'Next' }}"><i class="bi bi-chevron-right"></i></button>
                @endif
            </div>
        </div>
    </section>
    @endif

    {{-- Combo offers, in the banner-headed deal panel --}}
    @if($combos->count())
    <section class="pc-section pc-section-tint">
        <div class="container">
            <div class="pc-deal">
                <div class="pc-deal-head">
                    <div>
                        <h2><i class="bi bi-box2-heart-fill"></i> {{ $t('section_combos', 'কম্বো অফার', 'Combo Offers') }}</h2>
                        <p>{{ $t('section_combos_sub', 'একসাথে কিনলে দাম কম', 'Buy them together and pay less') }}</p>
                    </div>
                    <a href="{{ route('shop') }}" class="pc-deal-more">
                        {{ app()->getLocale() == 'bn' ? 'সব কম্বো' : 'All combos' }} <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="pc-deal-body">
                    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">
                        @foreach($combos as $product)
                            <div class="col">@include('partials.product-card', ['product' => $product])</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Best sellers --}}
    @if($bestSellers->count())
    <section class="pc-section">
        <div class="container">
            <div class="pc-section-head">
                <h2><i class="bi bi-star-fill"></i> {{ $t('section_bestsellers', 'জনপ্রিয় পণ্যসমূহ', 'Best Sellers') }}</h2>
                <a href="{{ route('shop') }}" class="pc-section-more">
                    {{ app()->getLocale() == 'bn' ? 'সব দেখুন' : 'View all' }} <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">
                @foreach($bestSellers as $product)
                    <div class="col">@include('partials.product-card', ['product' => $product])</div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Promo strip — Admin > Settings > Storefront Blocks > Promo Tiles --}}
    @if($promos->count())
    <section class="pc-section pc-section-tint">
        <div class="container">
            <div class="pc-promo-grid">
                @foreach($promos as $promo)
                    <a href="{{ $promo->link }}" class="pc-promo"
                       @if($promo->image_url) style="background-image: url('{{ $promo->image_url }}');" @endif
                       @if($promo->is_external) target="_blank" rel="noopener" @endif>
                        @if($promo->subtitle)<small>{{ $promo->subtitle }}</small>@endif
                        <h3>{{ strtr($promo->title, [':threshold' => number_format($threshold)]) }}</h3>
                        <span>
                            {{ app()->getLocale() == 'bn' ? 'দেখুন' : 'Browse' }}
                            <i class="bi bi-arrow-right"></i>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Featured --}}
    @if($featured->count())
    <section class="pc-section">
        <div class="container">
            <div class="pc-section-head">
                <h2><i class="bi bi-award-fill"></i> {{ $t('section_featured', 'নির্বাচিত পণ্য', 'Featured Products') }}</h2>
                <a href="{{ route('shop') }}" class="pc-section-more">
                    {{ app()->getLocale() == 'bn' ? 'সব দেখুন' : 'View all' }} <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">
                @foreach($featured as $product)
                    <div class="col">@include('partials.product-card', ['product' => $product])</div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Customer reviews — approved only; a customer's submission or an admin's
         own entry both sit hidden until then (Admin > Reviews). --}}
    @if($reviews->count())
    <section class="pc-section pc-section-tint">
        <div class="container">
            <div class="pc-section-head">
                <h2><i class="bi bi-chat-heart-fill"></i> {{ $t('section_reviews', 'কাস্টমার রিভিউ', 'Customer Reviews') }}</h2>
            </div>
            <div class="pc-carousel-wrap">
                <div class="pc-carousel pc-review-grid" data-carousel>
                    @foreach($reviews as $review)
                        <div class="pc-review-card">
                            <div class="pc-review-stars">{{ $review->stars }}</div>
                            @if($review->title)<div class="pc-review-title">{{ $review->title }}</div>@endif
                            <p class="pc-review-body">{{ $review->body }}</p>
                            <div class="pc-review-meta">
                                <div class="pc-review-avatar">{{ mb_substr($review->customer_name, 0, 1) }}</div>
                                <div>
                                    <div class="pc-review-name">
                                        {{ $review->customer_name }}
                                        @if($review->order_id)<i class="bi bi-patch-check-fill pc-review-verified" title="{{ app()->getLocale() == 'bn' ? 'যাচাইকৃত ক্রয়' : 'Verified purchase' }}"></i>@endif
                                    </div>
                                    @if($review->product)<div class="pc-review-product">{{ $review->product->name }}</div>@endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($reviews->count() > 3)
                    <button type="button" class="pc-carousel-nav pc-carousel-prev" data-carousel-prev aria-label="{{ app()->getLocale() == 'bn' ? 'আগেরটি' : 'Previous' }}"><i class="bi bi-chevron-left"></i></button>
                    <button type="button" class="pc-carousel-nav pc-carousel-next" data-carousel-next aria-label="{{ app()->getLocale() == 'bn' ? 'পরেরটি' : 'Next' }}"><i class="bi bi-chevron-right"></i></button>
                @endif
            </div>
        </div>
    </section>
    @endif

    {{-- Trending --}}
    @if($trending->count())
    <section class="pc-section pc-section-tint">
        <div class="container">
            <div class="pc-section-head">
                <h2><i class="bi bi-fire"></i> {{ $t('section_trending', 'ট্রেন্ডিং পণ্যসমূহ', 'Trending Now') }}</h2>
                <a href="{{ route('shop') }}" class="pc-section-more">
                    {{ app()->getLocale() == 'bn' ? 'সব দেখুন' : 'View all' }} <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">
                @foreach($trending as $product)
                    <div class="col">@include('partials.product-card', ['product' => $product])</div>
                @endforeach
            </div>
        </div>
    </section>
    @endif
@endsection

@push('scripts')
<script>
// Drives every .pc-carousel's arrow buttons by scrolling one viewport-width
// at a time; scroll-snap (in the CSS) settles the page, so this never has to
// compute item widths. Buttons disable themselves at either end.
document.querySelectorAll('[data-carousel]').forEach(function (track) {
    var wrap = track.closest('.pc-carousel-wrap');
    if (!wrap) return;
    var prev = wrap.querySelector('[data-carousel-prev]');
    var next = wrap.querySelector('[data-carousel-next]');
    if (!prev || !next) return;

    var page = function () { return track.clientWidth * 0.92; };
    prev.addEventListener('click', function () { track.scrollBy({ left: -page(), behavior: 'smooth' }); });
    next.addEventListener('click', function () { track.scrollBy({ left: page(), behavior: 'smooth' }); });

    var updateNav = function () {
        var max = track.scrollWidth - track.clientWidth - 1;
        prev.disabled = track.scrollLeft <= 0;
        next.disabled = track.scrollLeft >= max;
    };
    track.addEventListener('scroll', updateNav, { passive: true });
    window.addEventListener('resize', updateNav);
    updateNav();
});
</script>
@endpush
