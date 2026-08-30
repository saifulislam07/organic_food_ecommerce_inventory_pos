@php $features = $page->featureList(); @endphp

@if($features)
    <section class="lp-wrap lp-section">
        <h2 class="lp-h2">কেন কিনবেন</h2>
        <ul class="lp-features">
            @foreach($features as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    </section>
@endif
