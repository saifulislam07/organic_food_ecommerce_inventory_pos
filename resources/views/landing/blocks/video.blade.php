{{--
    The video, at the top of the page when there is no hero picture and as a
    block of its own when there is. Reels and Shorts are filmed upright, so
    they get an upright frame instead of a letterbox around a narrow strip.
--}}
@php $inHero = $inHero ?? false; @endphp

@if($page->videoEmbedUrl())
    <section class="lp-wrap lp-section {{ $inHero ? 'lp-media-section' : '' }}">
        @unless($inHero)
            {{-- A way into the order run for whoever is sold by the picture
                 and need not scroll past the rest to buy. --}}
            @if($takingOrders ?? false)
                <a class="lp-btn lp-btn-jump" href="#lp-buy">{{ $page->ctaText() }}</a>
            @endif

            <h2 class="lp-h2">ভিডিওতে দেখুন</h2>
        @endunless

        <div class="lp-hero-media {{ $page->videoIsUpright() ? 'is-upright' : '' }}">
            <iframe src="{{ $page->videoEmbedUrl() }}" title="{{ $page->headline }}"
                    loading="lazy" allowfullscreen
                    allow="accelerometer; autoplay; encrypted-media; picture-in-picture"></iframe>
        </div>
    </section>
@endif
