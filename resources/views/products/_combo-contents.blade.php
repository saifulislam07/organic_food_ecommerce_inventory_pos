{{--
    What a bundle is made of, priced part by part.

    The parts total is added up from what each component sells for today rather
    than read off the combo's stored compare price, so the arithmetic on screen
    always agrees with the lines above it.
--}}
@php
    $parts = $product->comboParts();
    $bn = app()->getLocale() == 'bn';
    $partsTotal = $parts->sum(fn ($item) => $item->quantity * (float) $item->component->display_price);
    $comboPrice = (float) ($product->variants->first()?->display_price ?? 0);
    $savings = $partsTotal - $comboPrice;
@endphp

@if($parts->isNotEmpty())
<div class="combo-contents">
    <h3 class="combo-contents-title">
        <i class="bi bi-box2-fill"></i>
        {{ $bn ? 'এই কম্বোতে যা যা আছে' : 'Inside this combo' }}
        <span class="combo-contents-count">{{ $parts->count() }}</span>
    </h3>

    <ul class="combo-parts">
        @foreach($parts as $item)
            @php
                $component = $item->component;
                $partProduct = $component->product;
                $lineTotal = $item->quantity * (float) $component->display_price;
            @endphp
            <li class="combo-part">
                <img class="combo-part-image" src="{{ $partProduct->image_url }}" alt="" loading="lazy">

                <div class="combo-part-body">
                    <span class="combo-part-name">
                        @if($partProduct->is_active)
                            <a href="{{ route('product.show', $partProduct->slug) }}">{{ $partProduct->name }}</a>
                        @else
                            {{ $partProduct->name }}
                        @endif
                    </span>
                    <span class="combo-part-measure">{{ $component->measure }}</span>
                </div>

                <span class="combo-part-qty">&times;{{ $item->quantity }}</span>
                <span class="combo-part-price">৳{{ number_format($lineTotal) }}</span>
            </li>
        @endforeach
    </ul>

    <div class="combo-totals">
        <div class="combo-total-row">
            <span>{{ $bn ? 'আলাদা কিনলে' : 'Bought separately' }}</span>
            <span class="combo-total-struck">৳{{ number_format($partsTotal) }}</span>
        </div>
        <div class="combo-total-row combo-total-row-final">
            <span>{{ $bn ? 'কম্বো দাম' : 'Combo price' }}</span>
            <span>৳{{ number_format($comboPrice) }}</span>
        </div>
        @if($savings > 0)
            <div class="combo-savings">
                <i class="bi bi-piggy-bank-fill"></i>
                {{ $bn ? 'আপনার সাশ্রয়' : 'You save' }}
                ৳{{ number_format($savings) }}
                <span>({{ round($savings / $partsTotal * 100) }}%)</span>
            </div>
        @endif
    </div>
</div>
@endif
