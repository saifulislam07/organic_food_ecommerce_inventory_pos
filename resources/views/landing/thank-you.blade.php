@extends('layouts.landing')

@section('title', 'অর্ডার সম্পন্ন — '.$page->headline)

@php
    // The one event the whole campaign is measured by, so it fires on the page
    // that only a completed order can reach.
    $pixelEvents = [[
        'name' => 'Purchase',
        'params' => [
            'value' => (float) $order->total,
            'currency' => 'BDT',
            'contents' => $order->items->map(fn ($item) => [
                'id' => (string) $item->product_variant_id,
                'quantity' => (int) $item->quantity,
            ])->all(),
            'num_items' => (int) $order->items->sum('quantity'),
        ],
    ]];
@endphp

@section('content')
<main class="lp-wrap lp-section">
    <div style="text-align:center;margin-bottom:20px;">
        <div style="width:64px;height:64px;margin:0 auto 12px;border-radius:50%;background:var(--primary,#3d8202);
                    color:#fff;font-size:2rem;line-height:64px;">✓</div>
        <h1 class="lp-h1" style="margin-bottom:6px;">
            {{ $page->thankyou_headline ?: 'অর্ডার সফল হয়েছে!' }}
        </h1>
        <p class="lp-sub">
            {{ $page->thankyou_body ?: 'আমাদের প্রতিনিধি শীঘ্রই আপনাকে ফোন করে অর্ডারটি নিশ্চিত করবেন।' }}
        </p>
    </div>

    <div class="lp-card">
        <div class="lp-total">
            <span>অর্ডার নম্বর</span>
            <strong style="color:var(--primary,#3d8202);">{{ $order->order_number }}</strong>
        </div>
        <div class="lp-total"><span>নাম</span><strong>{{ $order->customer_name }}</strong></div>
        <div class="lp-total"><span>মোবাইল</span><strong>{{ $order->customer_phone }}</strong></div>

        <hr style="border:0;border-top:1px dashed var(--gray-300,#dee2e6);margin:12px 0;">

        @foreach($order->items as $item)
            <div class="lp-total">
                <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                <strong>৳{{ number_format((float) $item->total) }}</strong>
            </div>
        @endforeach

        @if((float) $order->discount_amount > 0)
            <div class="lp-total">
                <span>কম্বো ছাড়</span>
                <strong style="color:var(--primary,#3d8202);">− ৳{{ number_format((float) $order->discount_amount) }}</strong>
            </div>
        @endif

        <div class="lp-total">
            <span>ডেলিভারি চার্জ</span>
            <strong>{{ (float) $order->delivery_charge > 0 ? '৳'.number_format((float) $order->delivery_charge) : 'ফ্রি' }}</strong>
        </div>

        <div class="lp-total is-grand">
            <span>সর্বমোট</span>
            <span>৳{{ number_format((float) $order->total) }}</span>
        </div>
    </div>

    @if($page->payment_mode === 'advance' && $page->payment_note)
        <div class="lp-alert lp-alert-note" style="margin-top:16px;white-space:pre-line;">{{ $page->payment_note }}</div>
    @else
        <div class="lp-alert lp-alert-info" style="margin-top:16px;">
            💵 পণ্য হাতে পেয়ে টাকা পরিশোধ করবেন।
        </div>
    @endif

    @php $whatsapp = \App\Support\Whatsapp::shopUrl('আমার অর্ডার নম্বর: '.$order->order_number); @endphp
    @if($whatsapp)
        <a class="lp-btn" style="background:#25d366;margin-top:8px;" href="{{ $whatsapp }}"
           target="_blank" rel="noopener">WhatsApp-এ যোগাযোগ করুন</a>
    @endif

    <a class="lp-btn" style="background:transparent;color:var(--primary,#3d8202);border:2px solid var(--primary,#3d8202);margin-top:10px;"
       href="{{ route('shop') }}">আরও পণ্য দেখুন</a>
</main>
@endsection
