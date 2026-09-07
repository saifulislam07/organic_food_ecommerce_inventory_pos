{{--
    The spec table.

    A gadget is bought on its numbers — warranty, model, capacity — and a
    paragraph hides them. Two columns, label left and value right, so a phone
    can scan it without reading it.
--}}
@if($page->specList())
    <section class="lp-wrap lp-section">
        <h2 class="lp-h2">স্পেসিফিকেশন</h2>

        <dl class="lp-specs">
            @foreach($page->specList() as $spec)
                <div class="lp-spec">
                    <dt>{{ $spec['label'] }}</dt>
                    <dd>{{ $spec['value'] ?? '' }}</dd>
                </div>
            @endforeach
        </dl>
    </section>
@endif
