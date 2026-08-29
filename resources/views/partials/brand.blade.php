{{--
    The MohiPure wordmark, in one place.

    A logo uploaded in Admin > Settings still wins — this is the packaged
    fallback, so a fresh install already looks like the brand instead of an
    emoji. WebP first (a third of the PNG's weight) with the PNG behind it.

    @include('partials.brand', ['size' => 'lg', 'onDark' => true])
--}}
@php
    $size = $size ?? '';
    $onDark = $onDark ?? false;
    $siteTitle = \App\Models\Setting::get('site_title', 'MohiPure');
    $uploaded = \App\Models\Setting::value('logo');
    $classes = trim('brand-logo '
        .($size ? "brand-logo-{$size} " : '')
        .($onDark ? 'brand-logo-on-dark ' : '')
        .($class ?? ''));
@endphp
@if($uploaded)
    <img src="{{ \App\Support\ImageStore::url($uploaded) }}" alt="{{ $siteTitle }}" class="{{ $classes }}">
@else
    <picture>
        <source srcset="{{ asset('assets/img/logo.webp') }}" type="image/webp">
        <img src="{{ asset('assets/img/logo.png') }}" alt="{{ $siteTitle }}"
             class="{{ $classes }}" width="900" height="249" decoding="async">
    </picture>
@endif
