@php
    use App\Models\Setting;
    use App\Support\AmountInWords;
    use App\Support\ImageStore;

    $bn = app()->getLocale() === 'bn';
    $t = fn ($en, $bengali) => $bn ? $bengali : $en;
    $money = fn ($n) => '৳'.number_format((float) $n);

    $siteTitle = Setting::get('site_title', 'BaburhashiBD');
    $logo = Setting::value('logo');

    // The domain the shop actually runs on, rather than one written into the
    // template — a rebrand should not leave the old address on every invoice.
    $host = parse_url((string) config('app.url'), PHP_URL_HOST);

    // A part payment is the only case where the balance is worth restating;
    // on an ordinary unpaid order it would just repeat the total.
    $showBalance = $order->paid_amount !== null && $order->amount_due > 0;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $t('Invoice', 'ইনভয়েস') }} — {{ $order->order_number }}</title>

    {{-- The rest of the panel loads this; the invoice never did, so its Bangla
         fell back to whatever the machine happened to have. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /*
         | A5, portrait. The sheet is drawn at its true size on screen too, so
         | what you see is what comes out of the printer rather than a wide page
         | that reflows the moment it is printed.
         */
        @page { size: A5 portrait; margin: 10mm 9mm; }

        * { box-sizing: border-box; }

        /* Hind Siliguri carries the Bangla; the Latin faces come first so
           English still sets in the system UI font. sans-serif goes LAST — put
           it earlier and it wins outright, which is what used to happen. */
        body {
            font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, 'Hind Siliguri', sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
            color: #16181d;
            margin: 0;
            padding: 16px;
            background: #eceef1;
            -webkit-font-smoothing: antialiased;
        }

        .sheet {
            width: 148mm;
            min-height: 210mm;
            padding: 10mm 9mm;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 8px 24px rgba(0,0,0,.08);
        }

        /* One hairline weight everywhere, so nothing shouts. */
        .rule { border: 0; border-top: 1px solid #d9dce1; margin: 0; }

        /* ------------------------------------------------------------ head */

        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10mm; }
        .brand { min-width: 0; }
        .brand img { height: 13mm; width: auto; max-width: 60mm; display: block; }
        .brand .wordmark { font-size: 15pt; font-weight: 700; letter-spacing: -.01em; margin: 0; }
        .brand .tagline { margin: 3px 0 0; font-size: 7.5pt; color: #6b7280; }

        .doc { text-align: right; white-space: nowrap; }
        .doc .kind { font-size: 13pt; font-weight: 600; letter-spacing: .16em; text-transform: uppercase; margin: 0; }
        .doc dl { margin: 6px 0 0; font-size: 8pt; }
        .doc dt { display: inline; color: #6b7280; }
        .doc dd { display: inline; margin: 0 0 0 4px; font-weight: 600; }
        .doc .line { margin-top: 2px; }

        /* ----------------------------------------------------------- panes */

        .parties { display: flex; gap: 8mm; margin: 5mm 0; }
        .party { flex: 1; min-width: 0; }
        .label {
            font-size: 6.5pt; font-weight: 700; letter-spacing: .14em;
            text-transform: uppercase; color: #8a9099; margin: 0 0 3px;
        }
        .party .who { font-weight: 600; }
        .party p { margin: 0; font-size: 8.5pt; color: #3f434a; word-wrap: break-word; }

        /* ----------------------------------------------------------- items */

        table { width: 100%; border-collapse: collapse; }
        thead th {
            font-size: 6.5pt; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
            color: #8a9099; text-align: left; padding: 0 0 4px;
            border-bottom: 1px solid #16181d;
        }
        tbody td { padding: 5px 0; border-bottom: 1px solid #eceef1; vertical-align: top; font-size: 8.5pt; }
        tbody tr:last-child td { border-bottom: 0; }
        .num { text-align: right; white-space: nowrap; }
        .mid { text-align: center; white-space: nowrap; }
        .item-name { font-weight: 600; }
        .item-variant { display: block; font-size: 7.5pt; color: #8a9099; }

        /* --------------------------------------------------------- totals */

        .totals { display: flex; justify-content: flex-end; margin-top: 4mm; }
        .totals table { width: 62mm; }
        .totals td { padding: 3px 0; font-size: 8.5pt; border: 0; }
        .totals .grand td {
            border-top: 1px solid #16181d; padding-top: 6px;
            font-size: 11pt; font-weight: 700;
        }
        .totals .settle td { color: #6b7280; font-size: 8pt; }
        .totals .settle.first td { padding-top: 6px; }
        .totals .due td { font-weight: 700; color: #16181d; }

        /* ---------------------------------------------------------- words */

        .words { margin-top: 4mm; padding-top: 3mm; border-top: 1px solid #d9dce1; }
        .words p { margin: 0; font-size: 8.5pt; font-weight: 600; }

        .notes { margin-top: 4mm; }
        .notes p { margin: 0; font-size: 8pt; color: #3f434a; }

        .foot { margin-top: 6mm; padding-top: 3mm; border-top: 1px solid #d9dce1; text-align: center; }
        .foot p { margin: 0; font-size: 7.5pt; color: #8a9099; }
        .foot .thanks { color: #3f434a; font-weight: 600; margin-bottom: 2px; }

        /* --------------------------------------------------------- screen */

        .toolbar { max-width: 148mm; margin: 0 auto 14px; display: flex; gap: 8px; justify-content: center; }
        .btn {
            font: inherit; font-size: 9pt; font-weight: 600; padding: 8px 18px; border-radius: 6px;
            border: 1px solid transparent; cursor: pointer; text-decoration: none; display: inline-flex;
            align-items: center; gap: 6px;
        }
        .btn-print { background: #16181d; color: #fff; }
        .btn-back { background: #fff; color: #3f434a; border-color: #d9dce1; }

        /* A true-size A5 preview is wider than a phone. Let the sheet shrink on
           screen rather than push the page sideways — printing is unaffected,
           since the print rules below re-fix it to the paper. */
        @media screen and (max-width: 170mm) {
            body { padding: 10px; }
            .sheet { width: 100%; min-height: 0; padding: 6mm; }
            .toolbar { max-width: 100%; }
        }

        @media print {
            body { background: #fff; padding: 0; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .toolbar { display: none; }

            /* The logo is the one piece of colour on the page; browsers drop
               images' colour in print by default. */
            .brand img { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            /* An order long enough to run onto a second sheet keeps its column
               headings, never splits a line across the fold, and never leaves
               the totals stranded on their own page. */
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            .totals, .words, .foot { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button onclick="window.print()" class="btn btn-print">
        {{ $t('Print', 'প্রিন্ট') }}
    </button>
    <a class="btn btn-back"
       href="{{ auth()->user()->isAdmin() ? route('admin.orders.show', $order) : route('customer.orders.show', $order->order_number) }}">
        {{ $t('Back', 'ফিরে যান') }}
    </a>
</div>

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

</body>
</html>
