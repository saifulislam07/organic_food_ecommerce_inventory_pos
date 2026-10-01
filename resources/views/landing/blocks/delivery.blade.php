@php
    $insideCharge = $page->deliveryChargeFor('dhaka_inside', 0);
    $outsideCharge = $page->deliveryChargeFor('dhaka_outside', 0);
    $threshold = $page->freeDeliveryThreshold();
@endphp

<section class="lp-wrap lp-section">
    <h2 class="lp-h2">ডেলিভারি ও পেমেন্ট</h2>

    <div class="lp-card">
        <div class="lp-info-row">
            <span class="lp-info-icon" aria-hidden="true">🚚</span>
            <div class="lp-info-body">
                @if($page->delivery_mode === 'free')
                    <div class="lp-info-title">সারা দেশে ফ্রি ডেলিভারি</div>
                @elseif($threshold > 0)
                    {{-- The offer leads; the charges are what happens short of it. --}}
                    <div class="lp-info-title">
                        {{ \App\Support\Bangla::money($threshold) }} বা তার বেশি অর্ডারে সারা দেশে
                        <span class="lp-free-tag">ফ্রি ডেলিভারি</span>
                    </div>
                    <div class="lp-info-sub">{{ \App\Support\Bangla::money($threshold) }}-এর কম অর্ডারে ডেলিভারি চার্জ:</div>
                    <div class="lp-total"><span>ঢাকার ভেতরে</span><strong>{{ \App\Support\Bangla::money($insideCharge) }}</strong></div>
                    <div class="lp-total"><span>ঢাকার বাইরে</span><strong>{{ \App\Support\Bangla::money($outsideCharge) }}</strong></div>
                @else
                    <div class="lp-info-title">ডেলিভারি চার্জ</div>
                    <div class="lp-total"><span>ঢাকার ভেতরে</span><strong>{{ \App\Support\Bangla::money($insideCharge) }}</strong></div>
                    <div class="lp-total"><span>ঢাকার বাইরে</span><strong>{{ \App\Support\Bangla::money($outsideCharge) }}</strong></div>
                @endif
            </div>
        </div>

        <div class="lp-info-row">
            <span class="lp-info-icon lp-info-icon-pay" aria-hidden="true">💵</span>
            <div class="lp-info-body">
                @if($page->payment_mode === 'advance')
                    <div class="lp-info-title">
                        অগ্রিম পেমেন্ট
                        @if($page->advance_amount)
                            — {{ \App\Support\Bangla::money((float) $page->advance_amount) }}
                        @endif
                    </div>
                @else
                    <div class="lp-info-title">ক্যাশ অন ডেলিভারি — পণ্য হাতে পেয়ে টাকা দিন</div>
                @endif

                @if($page->payment_note)
                    <p class="lp-info-note">{{ $page->payment_note }}</p>
                @endif
            </div>
        </div>
    </div>
</section>
