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
    <div class="lp-done">
        {{-- Paper bits scattered across the width, each falling on its own beat. --}}
        <div class="lp-confetti" aria-hidden="true">
            @foreach([4, 12, 20, 28, 36, 44, 52, 60, 68, 76, 84, 92] as $i => $left)
                <span style="left: {{ $left }}%; animation-delay: {{ ($i % 4) * 0.18 }}s;"></span>
            @endforeach
        </div>

        <div class="lp-done-mark" aria-hidden="true">✓</div>
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
            <strong style="color:var(--accent-text,#4f0e94);">{{ $order->order_number }}</strong>
        </div>
        <div class="lp-total"><span>নাম</span><strong>{{ $order->customer_name }}</strong></div>
        <div class="lp-total"><span>মোবাইল</span><strong>{{ $order->customer_phone }}</strong></div>

        <hr style="border:0;border-top:2px dashed var(--cream-dark,#dee2e6);margin:12px 0;">

        @foreach($order->items as $item)
            <div class="lp-total">
                <span>{{ $item->product_name }} × {{ \App\Support\Bangla::digits($item->quantity) }}</span>
                <strong>{{ \App\Support\Bangla::money((float) $item->total) }}</strong>
            </div>
        @endforeach

        @if((float) $order->discount_amount > 0)
            <div class="lp-total">
                <span>কম্বো ছাড়</span>
                <strong style="color:var(--primary,#4f0e94);">− {{ \App\Support\Bangla::money((float) $order->discount_amount) }}</strong>
            </div>
        @endif

        <div class="lp-total">
            <span>ডেলিভারি চার্জ</span>
            <strong>{{ (float) $order->delivery_charge > 0 ? \App\Support\Bangla::money((float) $order->delivery_charge) : 'ফ্রি' }}</strong>
        </div>

        <div class="lp-total is-grand">
            <span>সর্বমোট</span>
            <span>{{ \App\Support\Bangla::money((float) $order->total) }}</span>
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
        <a class="lp-btn lp-btn-whatsapp" href="{{ $whatsapp }}"
           target="_blank" rel="noopener">WhatsApp-এ যোগাযোগ করুন</a>
    @endif

    <a class="lp-btn lp-btn-ghost" href="{{ route('shop') }}">আরও পণ্য দেখুন</a>
</main>
@endsection
