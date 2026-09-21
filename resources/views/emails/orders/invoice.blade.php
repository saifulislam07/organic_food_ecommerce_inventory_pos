@php
    use App\Models\Setting;
    use App\Support\AmountInWords;
    use App\Support\ImageStore;

    $bn = app()->getLocale() === 'bn';
    $t = fn ($en, $bengali) => $bn ? $bengali : $en;
    $money = fn ($n) => '৳'.number_format((float) $n);

    $siteTitle = Setting::get('site_title', 'BaburhashiBD');
    $logo = Setting::value('logo');

    $host = parse_url((string) config('app.url'), PHP_URL_HOST);

    $showBalance = $order->paid_amount !== null && $order->amount_due > 0;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $t('Invoice', 'ইনভয়েস') }} — {{ $order->order_number }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">

    @include('admin.orders.invoice-style')
</head>
<body>

@include('admin.orders.invoice-content', ['order' => $order])

</body>
</html>
