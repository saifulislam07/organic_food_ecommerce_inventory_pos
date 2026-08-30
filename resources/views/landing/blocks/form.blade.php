{{--
    The order form. Server-rendered and plain: it posts and works with
    JavaScript switched off, which matters when the traffic is a paid ad and a
    blank page is a wasted click.
--}}
<h2 class="lp-h2">অর্ডার করতে নিচের তথ্য দিন</h2>

@if($errors->any())
    <div class="lp-alert lp-alert-bad">
        @foreach($errors->all() as $message)
            <div>{{ $message }}</div>
        @endforeach
    </div>
@endif

@unless($takingOrders)
    <div class="lp-alert lp-alert-note">
        {{ $open ? 'স্টক শেষ, এখন অর্ডার নেওয়া যাচ্ছে না।' : ($closedReason ?? 'এই অফারটি এখন বন্ধ আছে।') }}
    </div>
@endunless

<div class="lp-field">
    <label for="lp-name">আপনার নাম <span style="color:#c62828;">*</span></label>
    <input id="lp-name" type="text" name="customer_name" required autocomplete="name"
           class="@error('customer_name') is-bad @enderror"
           value="{{ old('customer_name') }}" placeholder="নাম লিখুন">
    @error('customer_name') <div class="lp-error">{{ $message }}</div> @enderror
</div>

<div class="lp-field">
    <label for="lp-phone">মোবাইল নম্বর <span style="color:#c62828;">*</span></label>
    <input id="lp-phone" type="tel" name="customer_phone" required autocomplete="tel"
           inputmode="numeric" class="@error('customer_phone') is-bad @enderror"
           value="{{ old('customer_phone') }}" placeholder="01XXXXXXXXX">
    @error('customer_phone') <div class="lp-error">{{ $message }}</div> @enderror
</div>

@if($page->asksFor('address'))
    <div class="lp-field">
        <label for="lp-address">ডেলিভারির ঠিকানা <span style="color:#c62828;">*</span></label>
        <textarea id="lp-address" name="customer_address" rows="2" required autocomplete="street-address"
                  class="@error('customer_address') is-bad @enderror"
                  placeholder="গ্রাম/রোড, থানা, জেলা">{{ old('customer_address') }}</textarea>
        @error('customer_address') <div class="lp-error">{{ $message }}</div> @enderror
    </div>
@endif

@if($page->asksFor('area'))
    <div class="lp-field">
        <label for="lp-area">ডেলিভারি এলাকা <span style="color:#c62828;">*</span></label>
        <select id="lp-area" name="customer_area" required data-area
                class="@error('customer_area') is-bad @enderror">
            <option value="dhaka_inside" @selected(old('customer_area', 'dhaka_inside') === 'dhaka_inside')>ঢাকার ভেতরে</option>
            <option value="dhaka_outside" @selected(old('customer_area') === 'dhaka_outside')>ঢাকার বাইরে</option>
        </select>
        @error('customer_area') <div class="lp-error">{{ $message }}</div> @enderror
    </div>
@else
    <input type="hidden" name="customer_area" value="dhaka_inside">
@endif

@if($page->asksFor('email'))
    <div class="lp-field">
        <label for="lp-email">ইমেইল</label>
        <input id="lp-email" type="email" name="email" autocomplete="email"
               class="@error('email') is-bad @enderror" value="{{ old('email') }}">
        @error('email') <div class="lp-error">{{ $message }}</div> @enderror
    </div>
@endif

@if($page->asksFor('note'))
    <div class="lp-field">
        <label for="lp-note">কিছু বলার থাকলে</label>
        <textarea id="lp-note" name="notes" rows="2" placeholder="ঐচ্ছিক">{{ old('notes') }}</textarea>
    </div>
@endif

<div class="lp-card" style="margin:16px 0;">
    <div class="lp-total">
        <span>পণ্যের দাম</span>
        <strong data-total-goods>৳{{ number_format($openingQuote['subtotal'] - $openingQuote['discount']) }}</strong>
    </div>
    <div class="lp-total">
        <span>ডেলিভারি চার্জ</span>
        <strong data-total-delivery>
            {{ $openingQuote['delivery'] > 0 ? '৳'.number_format($openingQuote['delivery']) : 'ফ্রি' }}
        </strong>
    </div>
    <div class="lp-total is-grand">
        <span>সর্বমোট</span>
        <span data-total-grand>৳{{ number_format($openingQuote['total']) }}</span>
    </div>
</div>

<button type="submit" class="lp-btn" @disabled(! $takingOrders)>
    {{ $page->ctaText() }}
</button>

<p style="text-align:center;font-size:.86rem;color:#7c876f;margin:10px 0 0;">
    অর্ডার নিশ্চিত করতে আমরা আপনাকে ফোন করবো।
</p>
