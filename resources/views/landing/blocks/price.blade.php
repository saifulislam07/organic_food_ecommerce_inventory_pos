{{--
    The headline price, high on the page where the decision starts.

    The struck-through price and the saving are always rendered, hidden when
    there is nothing to compare against — a package chosen later may have a
    saving to show, and JavaScript can only reveal an element that exists.
--}}
@php
    if ($page->isBundle()) {
        $now = $page->bundleTotal();
        $was = $page->bundleCompareTotal();
        $label = null;
    } elseif ($page->isMulti()) {
        $now = (float) ($items->map(fn ($item) => $item->price())->min() ?? 0);
        $was = null;
        $label = 'শুরু';
    } else {
        $now = $defaultItem?->price() ?? 0;
        $was = $defaultItem?->comparePrice();
        $label = null;
    }
@endphp

<div class="lp-price">
    @if($label)
        <span class="lp-price-label">{{ $label }}</span>
    @endif

    <span class="lp-price-now" data-price-now>৳{{ number_format($now) }}</span>

    <span class="lp-price-was" data-price-was @if(! $was) hidden @endif>
        ৳{{ number_format($was ?? 0) }}
    </span>

    <span class="lp-save" data-price-save @if(! $was) hidden @endif>
        ৳{{ number_format(($was ?? 0) - $now) }} সাশ্রয়
    </span>
</div>
