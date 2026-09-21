{{--
    Shown under both the title and the second line. Typing the free-delivery
    figure out by hand is what let the shipped "Free Delivery" card go on
    promising ৳2,000 after Settings said otherwise, so say here that it does
    not have to be typed at all.
--}}
<div class="form-text mt-1">
    <code>:threshold</code> লিখলে
    <a href="{{ route('admin.settings.index') }}">Settings</a>-এর ফ্রি ডেলিভারির
    টাকার অঙ্ক বসবে (এখন ৳{{ number_format((float) \App\Models\Setting::get('free_delivery_threshold', 2000)) }}),
    আর <code>:phone</code> লিখলে দোকানের ফোন নম্বর। হাতে লিখলে Settings বদলালেও পুরনো লেখাই থেকে যাবে।
</div>
