@php $reviews = $page->reviewList(); @endphp

@if($reviews)
    <section class="lp-wrap lp-section">
        <h2 class="lp-h2">ক্রেতারা যা বলছেন</h2>
        @foreach($reviews as $review)
            <div class="lp-review">
                <div class="lp-stars">{{ str_repeat('★', max(1, min(5, (int) ($review['rating'] ?? 5)))) }}</div>
                <p style="margin:2px 0 4px;">{{ $review['text'] }}</p>
                @if(filled($review['name'] ?? null))
                    <div class="lp-review-name">— {{ $review['name'] }}</div>
                @endif
            </div>
        @endforeach
    </section>
@endif
