@php $images = $page->galleryImages(); @endphp

@if($images)
    <section class="lp-wrap lp-section">
        <h2 class="lp-h2">ছবিতে দেখুন</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;">
            @foreach($images as $image)
                <img src="{{ $image }}" alt="{{ $page->headline }}" loading="lazy"
                     style="border-radius:10px;aspect-ratio:1;object-fit:cover;">
            @endforeach
        </div>
    </section>
@endif
