{{--
    Meta Pixel.

    @include('partials.meta-pixel', [
        'pixelId' => $page->pixelId(),
        'events'  => [['name' => 'ViewContent', 'params' => ['value' => 1200, 'currency' => 'BDT']]],
    ])

    Nothing renders when no pixel is configured — a shop that never filled the
    box in should not be loading Facebook's script at all.

    PageView fires on every page; anything in $events fires after it.
--}}
@php
    $pixelId = trim((string) ($pixelId ?? ''));
    $events = $events ?? [];
@endphp

@if($pixelId !== '')
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,'script','https://connect.facebook.net/en_US/fbevents.js');

        fbq('init', @json($pixelId));
        fbq('track', 'PageView');
        @foreach($events as $event)
            fbq('track', @json($event['name']), @json((object) ($event['params'] ?? [])));
        @endforeach
    </script>
    <noscript>
        <img height="1" width="1" style="display:none" alt=""
             src="https://www.facebook.com/tr?id={{ urlencode($pixelId) }}&ev=PageView&noscript=1">
    </noscript>
@endif
