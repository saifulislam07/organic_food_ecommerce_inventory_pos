@php $reviews = $page->reviewList(); @endphp

@if($reviews)
    <section class="lp-wrap lp-section">
        <h2 class="lp-h2">ক্রেতারা যা বলছেন</h2>
        <div class="lp-reviews">
            @foreach($reviews as $review)
                <figure class="lp-review">
                    <div class="lp-stars">{{ str_repeat('★', max(1, min(5, (int) ($review['rating'] ?? 5)))) }}</div>
                    <blockquote>{{ $review['text'] }}</blockquote>
                    @if(filled($review['name'] ?? null))
                        <figcaption class="lp-review-name">
                            <span class="lp-avatar" aria-hidden="true">{{ mb_substr($review['name'], 0, 1) }}</span>
                            {{ $review['name'] }}
                        </figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </section>
@endif
