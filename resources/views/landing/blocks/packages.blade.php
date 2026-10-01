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
        <div class="lp-h2"><span aria-hidden="true">🎁</span> প্যাকেজে যা যা থাকছে</div>
        @foreach($items as $item)
            <div class="lp-pack lp-pack-static">
                @if($item->imageUrl())
                    <img src="{{ $item->imageUrl() }}" alt="{{ $item->label() }}" loading="lazy">
                @endif
                <div class="lp-pack-body">
                    <div class="lp-pack-name">{{ $item->label() }}</div>
                    <div class="lp-pack-meta">{{ \App\Support\Bangla::digits($item->min_qty) }}টি</div>
                </div>
            </div>
        @endforeach
    </div>

@elseif($page->isMulti())
    {{--
        A shopping-app picker rather than a dropdown of numbers: "এটা নিতে চাই"
        on each product, turning into − 1 + once it is in. Underneath is a
        plain number box with the same name, so the form still posts with
        JavaScript off — landing.js only adds the buttons.
    --}}
    <div class="lp-h2"><span aria-hidden="true">🧸</span> যা যা নিতে চান বেছে নিন</div>
    <p class="lp-pick-hint">যেটা নিতে চান তার পাশে <b>এটা নিতে চাই</b> চাপুন — একসাথে একাধিক পণ্য নিতে পারবেন।</p>

    @php $pickedCount = 0; $pickedTotal = 0.0; @endphp

    @foreach($items as $item)
        @php
            $range = $item->quantityRange();
            $min = $range ? $range[0] : 0;
            $max = $range ? end($range) : 0;
            $qty = $range ? (int) old("items.{$item->id}.qty", $item->is_default ? $min : 0) : 0;
            $qty = $qty > 0 ? max($min, min($qty, $max)) : 0;
            $pickedCount += $qty;
            $pickedTotal += $qty * $item->price();
        @endphp
        <div class="lp-pack lp-pick {{ $range ? '' : 'is-out' }} {{ $qty > 0 ? 'is-picked' : '' }}" data-pick>
            @if($item->imageUrl())
                <img src="{{ $item->imageUrl() }}" alt="{{ $item->label() }}" loading="lazy">
            @endif
            <div class="lp-pack-body">
                <div class="lp-pack-name">{{ $item->label() }}</div>

                <div class="lp-pick-row">
                    <div>
                        <span class="lp-pack-price">{{ \App\Support\Bangla::money($item->price()) }}</span>
                        @if($item->comparePrice())
                            <span class="lp-pack-was">{{ \App\Support\Bangla::money($item->comparePrice()) }}</span>
                        @endif
                    </div>

                    @if($range)
                        <div class="lp-stepper" data-stepper data-min="{{ $min }}" data-max="{{ $max }}">
                            <button type="button" class="lp-add" data-add>এটা নিতে চাই</button>
                            <div class="lp-step">
                                <button type="button" data-dec aria-label="একটি কমান">−</button>
                                <input type="number" name="items[{{ $item->id }}][qty]" value="{{ $qty }}"
                                       min="0" max="{{ $max }}" inputmode="numeric"
                                       aria-label="{{ $item->label() }} — পরিমাণ"
                                       data-price="{{ $item->price() }}" data-qty>
                                <button type="button" data-inc aria-label="একটি বাড়ান">+</button>
                            </div>
                        </div>
                    @else
                        <div class="lp-pack-out">স্টক শেষ</div>
                    @endif
                </div>

                @if($min > 1)
                    <div class="lp-pack-meta">কমপক্ষে {{ \App\Support\Bangla::digits($min) }}টি নিতে হবে</div>
                @endif
            </div>
        </div>
    @endforeach

    <div class="lp-pick-summary {{ $pickedCount ? '' : 'is-empty' }}" data-pick-summary aria-live="polite">
        @if($pickedCount)
            {{ \App\Support\Bangla::digits($pickedCount) }}টি পণ্য বেছে নিয়েছেন — {{ \App\Support\Bangla::money($pickedTotal) }}
        @else
            এখনো কোনো পণ্য বেছে নেননি
        @endif
    </div>

@else
    @php $chosen = $defaultItem; @endphp

    @if($items->count() > 1)
        <div class="lp-h2"><span aria-hidden="true">🧸</span> প্যাকেজ বেছে নিন</div>
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
                    <span class="lp-pack-price">{{ \App\Support\Bangla::money($item->price()) }}</span>
                    @if($item->comparePrice())
                        <span class="lp-pack-was">{{ \App\Support\Bangla::money($item->comparePrice()) }}</span>
                    @endif
                </div>
                @unless($available)
                    <div class="lp-pack-out">স্টক শেষ</div>
                @endunless
            </div>
        </label>
    @endforeach

    <div class="lp-qty">
        <label for="lp-qty">পরিমাণ</label>
        <select id="lp-qty" name="quantity" data-qty>
            @for($number = 1; $number <= $maxQty; $number++)
                <option value="{{ $number }}" @selected($number === (int) ($chosen?->min_qty ?? 1))>{{ \App\Support\Bangla::digits($number) }}</option>
            @endfor
        </select>
    </div>
@endif
