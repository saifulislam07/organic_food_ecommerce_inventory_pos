@php $images = $page->galleryImages(); @endphp

@if($images)
    <section class="lp-wrap lp-section">
        <h2 class="lp-h2">ছবিতে দেখুন</h2>
        <div class="lp-gallery">
            @foreach($images as $image)
                <img src="{{ $image }}" alt="{{ $page->headline }}" loading="lazy">
            @endforeach
        </div>
    </section>
@endif
