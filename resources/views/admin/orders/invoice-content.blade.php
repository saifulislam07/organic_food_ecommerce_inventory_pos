@php
    use App\Models\Setting;
    use App\Support\AmountInWords;
    use App\Support\ImageStore;
@endphp
<div class="sheet">

    <div class="head">
        <div class="brand">
            {{-- An uploaded logo wins; otherwise the packaged wordmark, so a
                 fresh install still prints as the brand. Same rule as
                 partials.brand, inlined because this page has no app layout. --}}
            @if($logo)
                <img src="{{ ImageStore::url($logo) }}" alt="{{ $siteTitle }}">
            @else
                <picture>
                    <source srcset="{{ asset('assets/img/logo.webp') }}" type="image/webp">
                    <img src="{{ asset('assets/img/logo.png') }}" alt="{{ $siteTitle }}">
                </picture>
            @endif
            <p class="tagline">{{ $t('Your Trusted Kids Store Partner', 'আপনার বিশ্বস্ত শিশু পণ্যের পার্টনার') }}</p>
        </div>

        <div class="doc">
            <p class="kind">{{ $t('Invoice', 'ইনভয়েস') }}</p>
            <dl>
                <div class="line"><dt>{{ $t('No.', 'নং') }}</dt><dd>{{ $order->order_number }}</dd></div>
                <div class="line"><dt>{{ $t('Date', 'তারিখ') }}</dt><dd>{{ $order->created_at->format('d M Y') }}</dd></div>
                <div class="line"><dt>{{ $t('Status', 'অবস্থা') }}</dt><dd>{{ strtoupper($order->status_label) }}</dd></div>
            </dl>
        </div>
    </div>

    <hr class="rule" style="margin-top: 5mm;">

    <div class="parties">
        <div class="party">
            <p class="label">{{ $t('Billed to', 'গ্রাহক') }}</p>
            <p class="who">{{ $order->customer_name }}</p>
            <p>{{ $order->customer_phone }}</p>
            <p>{{ $order->customer_address }}</p>
            @if($order->customer_area)
                <p>{{ ucwords(str_replace('_', ' ', $order->customer_area)) }}</p>
            @endif
        </div>
        <div class="party">
            <p class="label">{{ $t('From', 'প্রেরক') }}</p>
            <p class="who">{{ $siteTitle }}</p>
            <p>{{ Setting::get('phone', '+880 1XXX-XXXXXX') }}</p>
            <p>{{ Setting::get('address', 'Dhaka, Bangladesh') }}</p>
            @if($host)<p>{{ $host }}</p>@endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ $t('Description', 'পণ্য') }}</th>
                <th class="mid" style="width: 12mm;">{{ $t('Qty', 'পরিমাণ') }}</th>
                <th class="num" style="width: 22mm;">{{ $t('Rate', 'দর') }}</th>
                <th class="num" style="width: 24mm;">{{ $t('Amount', 'মোট') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>
                    <span class="item-name">{{ $item->product_name }}</span>
                    @if($item->variant_name)
                        <span class="item-variant">{{ $item->variant_name }}</span>
                    @endif
                </td>
                <td class="mid">{{ $item->quantity }}</td>
                <td class="num">{{ $money($item->unit_price) }}</td>
                <td class="num">{{ $money($item->total) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tbody>
                <tr>
                    <td>{{ $t('Subtotal', 'সাবটোটাল') }}</td>
                    <td class="num">{{ $money($order->subtotal) }}</td>
                </tr>
                @if($order->discount_amount > 0)
                <tr>
                    <td>
                        {{ $t('Discount', 'ডিসকাউন্ট') }}
                        @if($order->coupon_code)<span class="item-variant" style="display:inline">({{ $order->coupon_code }})</span>@endif
                    </td>
                    <td class="num">−{{ $money($order->discount_amount) }}</td>
                </tr>
                @endif
                <tr>
                    <td>{{ $t('Delivery', 'ডেলিভারি') }}</td>
                    <td class="num">{{ $money($order->delivery_charge) }}</td>
                </tr>
                <tr class="grand">
                    <td>{{ $t('Total', 'সর্বমোট') }}</td>
                    <td class="num">{{ $money($order->total) }}</td>
                </tr>

                {{-- Counter sales record what was handed over, so the slip shows
                     the change; a part payment shows what is still owed. --}}
                @if($order->paid_amount !== null)
                <tr class="settle first">
                    <td>{{ $t('Paid', 'জমা') }}</td>
                    <td class="num">{{ $money($order->paid_amount) }}</td>
                </tr>
                @if($order->change_due > 0)
                <tr class="settle">
                    <td>{{ $t('Change', 'ফেরত') }}</td>
                    <td class="num">{{ $money($order->change_due) }}</td>
                </tr>
                @endif
                @if($showBalance)
                <tr class="settle due">
                    <td>{{ $t('Balance due', 'বাকি') }}</td>
                    <td class="num">{{ $money($order->amount_due) }}</td>
                </tr>
                @endif
                @endif
            </tbody>
        </table>
    </div>

    {{-- The figure written out. A printed invoice is a document people argue
         over, and a total in words is what settles it: a digit can be altered
         by hand, a sentence cannot. --}}
    <div class="words">
        <p class="label">{{ $t('Amount in words', 'কথায়') }}</p>
        <p>{{ AmountInWords::taka($order->total) }}</p>
    </div>

    @if($order->notes)
    <div class="notes">
        <p class="label">{{ $t('Notes', 'অতিরিক্ত তথ্য') }}</p>
        <p>{{ $order->notes }}</p>
    </div>
    @endif

    <div class="foot">
        <p class="thanks">{{ $t('Thank you for shopping with '.$siteTitle.'.', 'আমাদের কাছ থেকে কেনাকাটা করার জন্য ধন্যবাদ!') }}</p>
        <p>{{ $t('This is a computer generated invoice.', 'এটি একটি কম্পিউটার জেনারেটেড ইনভয়েস') }}</p>
    </div>

</div>
