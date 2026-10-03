@php
    use App\Models\Setting;
    use App\Support\AmountInWords;

    $bn = app()->getLocale() === 'bn';
    $t = fn ($en, $bengali) => $bn ? $bengali : $en;
    $money = fn ($n) => '৳'.number_format((float) $n);

    $siteTitle = Setting::get('site_title', 'BaburhashiBD');

    // A file path, not a URL: mPDF reads it straight off disk instead of
    // fetching it over HTTP from the very site that is sending the mail.
    $logo = Setting::value('logo');
    $logoPath = $logo && is_file(public_path($logo)) ? public_path($logo) : public_path('assets/img/logo.png');

    $host = parse_url((string) config('app.url'), PHP_URL_HOST);

    $showBalance = $order->paid_amount !== null && $order->amount_due > 0;
@endphp
<html>
<head>
<style>
    body { font-family: hindsiliguri; font-size: 9.5pt; line-height: 1.45; color: #16181d; }
    table { width: 100%; border-collapse: collapse; }
    td, th { vertical-align: top; }

    .muted { color: #6b7280; }
    .label { font-size: 6.5pt; font-weight: bold; letter-spacing: 1pt; text-transform: uppercase; color: #8a9099; }
    .kind { font-size: 13pt; font-weight: bold; letter-spacing: 2pt; text-transform: uppercase; }
    .meta { font-size: 8pt; }
    .party { font-size: 8.5pt; color: #3f434a; }
    .who { font-weight: bold; color: #16181d; }

    .items th {
        font-size: 6.5pt; font-weight: bold; letter-spacing: 1pt; text-transform: uppercase;
        color: #8a9099; text-align: left; padding-bottom: 4px; border-bottom: 1px solid #16181d;
    }
    .items td { padding: 5px 0; border-bottom: 1px solid #eceef1; font-size: 8.5pt; }
    .num { text-align: right; white-space: nowrap; }
    .mid { text-align: center; }
    .item-name { font-weight: bold; }
    .item-variant { font-size: 7.5pt; color: #8a9099; }

    .totals td { padding: 3px 0; font-size: 8.5pt; }
    .totals .grand td { border-top: 1px solid #16181d; padding-top: 6px; font-size: 11pt; font-weight: bold; }
    .totals .settle td { color: #6b7280; font-size: 8pt; }
    .totals .due td { font-weight: bold; color: #16181d; }

    .block { margin-top: 4mm; padding-top: 3mm; border-top: 1px solid #d9dce1; }
    .foot { margin-top: 6mm; padding-top: 3mm; border-top: 1px solid #d9dce1; text-align: center; font-size: 7.5pt; color: #8a9099; }
</style>
</head>
<body>

<table>
    <tr>
        <td style="width: 60%;">
            <img src="{{ $logoPath }}" style="height: 13mm;">
            <div class="muted" style="font-size: 7.5pt; margin-top: 3px;">{{ $t('Your Trusted Kids Store Partner', 'আপনার বিশ্বস্ত শিশু পণ্যের পার্টনার') }}</div>
        </td>
        <td style="width: 40%; text-align: right;">
            <div class="kind">{{ $t('Invoice', 'ইনভয়েস') }}</div>
            <div class="meta"><span class="muted">{{ $t('No.', 'নং') }}</span> <b>{{ $order->order_number }}</b></div>
            <div class="meta"><span class="muted">{{ $t('Date', 'তারিখ') }}</span> <b>{{ $order->created_at->format('d M Y') }}</b></div>
            <div class="meta"><span class="muted">{{ $t('Status', 'অবস্থা') }}</span> <b>{{ strtoupper($order->status_label) }}</b></div>
        </td>
    </tr>
</table>

<table style="margin: 5mm 0; border-top: 1px solid #d9dce1;">
    <tr>
        <td style="width: 50%; padding: 4mm 4mm 0 0;" class="party">
            <div class="label">{{ $t('Billed to', 'গ্রাহক') }}</div>
            <div class="who">{{ $order->customer_name }}</div>
            <div>{{ $order->customer_phone }}</div>
            <div>{{ $order->customer_address }}</div>
            @if($order->customer_area)
                <div>{{ ucwords(str_replace('_', ' ', $order->customer_area)) }}</div>
            @endif
        </td>
        <td style="width: 50%; padding: 4mm 0 0 4mm;" class="party">
            <div class="label">{{ $t('From', 'প্রেরক') }}</div>
            <div class="who">{{ $siteTitle }}</div>
            <div>{{ Setting::get('phone', '+880 1XXX-XXXXXX') }}</div>
            <div>{{ Setting::get('address', 'Dhaka, Bangladesh') }}</div>
            @if($host)<div>{{ $host }}</div>@endif
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th>{{ $t('Description', 'পণ্য') }}</th>
            <th class="mid" style="width: 12mm; text-align: center;">{{ $t('Qty', 'পরিমাণ') }}</th>
            <th class="num" style="width: 22mm; text-align: right;">{{ $t('Rate', 'দর') }}</th>
            <th class="num" style="width: 24mm; text-align: right;">{{ $t('Amount', 'মোট') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
        <tr>
            <td>
                <span class="item-name">{{ $item->product_name }}</span>
                @if($item->variant_name)
                    <br><span class="item-variant">{{ $item->variant_name }}</span>
                @endif
            </td>
            <td class="mid">{{ $item->quantity }}</td>
            <td class="num">{{ $money($item->unit_price) }}</td>
            <td class="num">{{ $money($item->total) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table class="totals" style="width: 62mm; margin-top: 4mm;" align="right">
    <tr>
        <td>{{ $t('Subtotal', 'সাবটোটাল') }}</td>
        <td class="num">{{ $money($order->subtotal) }}</td>
    </tr>
    @if($order->discount_amount > 0)
    <tr>
        <td>
            {{ $t('Discount', 'ডিসকাউন্ট') }}
            @if($order->coupon_code)<span class="item-variant">({{ $order->coupon_code }})</span>@endif
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
    @if($order->paid_amount !== null)
    <tr class="settle">
        <td style="padding-top: 6px;">{{ $t('Paid', 'জমা') }}</td>
        <td class="num" style="padding-top: 6px;">{{ $money($order->paid_amount) }}</td>
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
</table>

<div style="clear: both;"></div>

<div class="block">
    <div class="label">{{ $t('Amount in words', 'কথায়') }}</div>
    <div style="font-size: 8.5pt; font-weight: bold;">{{ AmountInWords::taka($order->total) }}</div>
</div>

@if($order->notes)
<div style="margin-top: 4mm;">
    <div class="label">{{ $t('Notes', 'অতিরিক্ত তথ্য') }}</div>
    <div style="font-size: 8pt; color: #3f434a;">{{ $order->notes }}</div>
</div>
@endif

<div class="foot">
    <div style="color: #3f434a; font-weight: bold;">{{ $t('Thank you for shopping with '.$siteTitle.'.', 'আমাদের কাছ থেকে কেনাকাটা করার জন্য ধন্যবাদ!') }}</div>
    <div>{{ $t('This is a computer generated invoice.', 'এটি একটি কম্পিউটার জেনারেটেড ইনভয়েস') }}</div>
</div>

</body>
</html>
