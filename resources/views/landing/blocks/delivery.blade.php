@php
    $insideCharge = $page->deliveryChargeFor('dhaka_inside', 0);
    $outsideCharge = $page->deliveryChargeFor('dhaka_outside', 0);
    $threshold = (float) \App\Models\Setting::get('free_delivery_threshold', 2000);
@endphp

<section class="lp-wrap lp-section">
    <h2 class="lp-h2">ডেলিভারি ও পেমেন্ট</h2>

    <div class="lp-card">
        @if($page->delivery_mode === 'free')
            <div style="font-weight:700;color:var(--primary,#3d8202);">🚚 সারা দেশে ফ্রি ডেলিভারি</div>
        @else
            <div class="lp-total"><span>ঢাকার ভেতরে</span><strong>৳{{ number_format($insideCharge) }}</strong></div>
            <div class="lp-total"><span>ঢাকার বাইরে</span><strong>৳{{ number_format($outsideCharge) }}</strong></div>

            @if($page->delivery_mode === 'global' && $threshold > 0)
                <p style="margin:8px 0 0;font-size:.9rem;color:var(--primary-dark,#2f6b02);">
                    ৳{{ number_format($threshold) }} টাকার বেশি অর্ডারে ডেলিভারি ফ্রি।
                </p>
            @endif
        @endif

        <hr style="border:0;border-top:1px dashed var(--gray-300,#dee2e6);margin:14px 0;">

        @if($page->payment_mode === 'advance')
            <div style="font-weight:700;">
                অগ্রিম পেমেন্ট
                @if($page->advance_amount)
                    — ৳{{ number_format((float) $page->advance_amount) }}
                @endif
            </div>
            @if($page->payment_note)
                <p style="margin:6px 0 0;white-space:pre-line;">{{ $page->payment_note }}</p>
            @endif
        @else
            <div style="font-weight:700;">💵 ক্যাশ অন ডেলিভারি — পণ্য হাতে পেয়ে টাকা দিন</div>
            @if($page->payment_note)
                <p style="margin:6px 0 0;white-space:pre-line;">{{ $page->payment_note }}</p>
            @endif
        @endif
    </div>
</section>
