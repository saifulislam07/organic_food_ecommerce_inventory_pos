@php $faqs = $page->faqList(); @endphp

@if($faqs)
    <section class="lp-wrap lp-section">
        <h2 class="lp-h2">সাধারণ প্রশ্ন</h2>
        @foreach($faqs as $faq)
            <details class="lp-faq">
                <summary>{{ $faq['q'] }}</summary>
                <p>{{ $faq['a'] }}</p>
            </details>
        @endforeach
    </section>
@endif
