{{--
    The three figures under the hero button. The same on every slide, so it
    lives here rather than being repeated in each carousel item.
--}}
<div class="hero-stats">
    <div class="hero-stat">
        <div class="hero-stat-num">500+</div>
        <div class="hero-stat-label">{{ app()->getLocale() == 'bn' ? 'সন্তুষ্ট গ্রাহক' : 'Happy Customers' }}</div>
    </div>
    <div class="hero-stat">
        <div class="hero-stat-num">50+</div>
        <div class="hero-stat-label">{{ app()->getLocale() == 'bn' ? 'পণ্যসমূহ' : 'Products' }}</div>
    </div>
    <div class="hero-stat">
        <div class="hero-stat-num">100%</div>
        <div class="hero-stat-label">{{ app()->getLocale() == 'bn' ? 'অর্গানিক' : 'Organic' }}</div>
    </div>
</div>
