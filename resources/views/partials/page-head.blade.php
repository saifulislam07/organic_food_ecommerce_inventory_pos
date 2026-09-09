{{--
    The title strip at the top of an inner page.

    Shop, cart, checkout and the account pages each carried their own copy of
    this banner and its breadcrumb CSS; the three had already drifted apart.

    $crumbs is read as label => url for a link, or a bare string for the page
    you are already on. Home is prepended, so a call only names what follows it:

        @include('partials.page-head', [
            'title' => 'Cart',
            'icon' => 'cart3',
            'crumbs' => ['Shop' => route('shop'), 'Cart'],
        ])
--}}
@php
    $icon = $icon ?? null;
    $lead = $lead ?? null;
    $crumbs = $crumbs ?? [];
@endphp
<div class="pc-page-head">
    <div class="container">
        <h1>
            @if($icon)<i class="bi bi-{{ $icon }}"></i>@endif
            {{ $title }}
        </h1>
        @if($lead)
            <p class="pc-page-lead">{{ $lead }}</p>
        @endif
        @if($crumbs)
            <ul class="pc-crumbs">
                <li><a href="{{ route('home') }}">{{ app()->getLocale() == 'bn' ? 'হোম' : 'Home' }}</a></li>
                @foreach($crumbs as $label => $url)
                    <li class="pc-crumb-sep">/</li>
                    <li>
                        {{-- An integer key means the entry was written without a URL. --}}
                        @if(is_int($label))
                            {{ $url }}
                        @else
                            <a href="{{ $url }}">{{ $label }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
