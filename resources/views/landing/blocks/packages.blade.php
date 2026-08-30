{{--
    What is on offer, and how it is picked.

    Three shapes from one partial: choose one package, take several with
    quantities, or one fixed bundle. Prices shown here are for the visitor's
    benefit only — LandingPageOrder looks every one of them up again.
--}}
@php
    $maxQty = (int) max(1, $items->max('max_qty'));
@endphp

@if($page->isBundle())
    <div class="lp-card">
        <div class="lp-h2" style="font-size:1.05rem;">প্যাকেজে যা যা থাকছে</div>
        @foreach($items as $item)
            <div class="lp-pack" style="cursor:default;">
                @if($item->imageUrl())
                    <img src="{{ $item->imageUrl() }}" alt="{{ $item->label() }}" loading="lazy">
                @endif
                <div class="lp-pack-body">
                    <div class="lp-pack-name">{{ $item->label() }}</div>
                    <div style="font-size:.85rem;color:#7c876f;">{{ $item->min_qty }} টি</div>
                </div>
            </div>
        @endforeach
    </div>

@elseif($page->isMulti())
    <div class="lp-h2">যা যা নিতে চান বেছে নিন</div>

    @foreach($items as $item)
        @php $range = $item->quantityRange(); @endphp
        <label class="lp-pack {{ $range ? '' : 'is-out' }}" style="cursor:default;">
            @if($item->imageUrl())
                <img src="{{ $item->imageUrl() }}" alt="{{ $item->label() }}" loading="lazy">
            @endif
            <div class="lp-pack-body">
                <div class="lp-pack-name">{{ $item->label() }}</div>
                <div>
                    <span class="lp-pack-price">৳{{ number_format($item->price()) }}</span>
                    @if($item->comparePrice())
                        <span class="lp-pack-was">৳{{ number_format($item->comparePrice()) }}</span>
                    @endif
                </div>
                @unless($range)
                    <div style="font-size:.85rem;color:#a52117;">স্টক শেষ</div>
                @endunless
            </div>

            @if($range)
                <select name="items[{{ $item->id }}][qty]" data-price="{{ $item->price() }}" data-qty>
                    <option value="0">০</option>
                    @foreach($range as $number)
                        <option value="{{ $number }}" @selected($item->is_default && $number === $item->min_qty)>
                            {{ $number }}
                        </option>
                    @endforeach
                </select>
            @endif
        </label>
    @endforeach

@else
    @php $chosen = $defaultItem; @endphp

    @if($items->count() > 1)
        <div class="lp-h2">প্যাকেজ বেছে নিন</div>
    @endif

    @foreach($items as $item)
        @php $available = $item->inStock(); @endphp
        <label class="lp-pack {{ $available ? '' : 'is-out' }}">
            <input type="radio" name="item_id" value="{{ $item->id }}"
                   data-price="{{ $item->price() }}"
                   data-compare="{{ $item->comparePrice() ?? '' }}"
                   data-max="{{ $item->max_qty }}"
                   data-min="{{ $item->min_qty }}"
                   @checked($chosen && $item->id === $chosen->id)
                   @disabled(! $available)>

            @if($item->imageUrl())
                <img src="{{ $item->imageUrl() }}" alt="{{ $item->label() }}" loading="lazy">
            @endif

            {{--
                The price sits under the name rather than beside it: on a
                360px phone a nowrap price next to a Bengali product name
                squeezes the name into four lines.
            --}}
            <div class="lp-pack-body">
                <div class="lp-pack-name">{{ $item->label() }}</div>
                <div>
                    <span class="lp-pack-price">৳{{ number_format($item->price()) }}</span>
                    @if($item->comparePrice())
                        <span class="lp-pack-was">৳{{ number_format($item->comparePrice()) }}</span>
                    @endif
                </div>
                @unless($available)
                    <div style="font-size:.85rem;color:#a52117;">স্টক শেষ</div>
                @endunless
            </div>
        </label>
    @endforeach

    <div class="lp-qty">
        <label for="lp-qty" style="font-weight:600;">পরিমাণ</label>
        <select id="lp-qty" name="quantity" data-qty>
            @for($number = 1; $number <= $maxQty; $number++)
                <option value="{{ $number }}" @selected($number === (int) ($chosen?->min_qty ?? 1))>{{ $number }}</option>
            @endfor
        </select>
    </div>
@endif

@if($page->stock_note)
    <p style="margin:12px 0 0;color:var(--accent-text,#b85600);font-weight:600;">
        ⚡ {{ $page->stock_note }}
    </p>
@endif
