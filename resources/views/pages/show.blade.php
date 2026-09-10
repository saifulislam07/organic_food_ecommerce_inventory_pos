@extends('layouts.frontend')

@section('title', $page->title . ' – ' . \App\Models\Setting::get('site_title', 'BaburhashiBD'))

@php $bn = app()->getLocale() === 'bn'; @endphp

@section('content')
@include('partials.page-head', [
    'title' => $page->title,
    'icon' => 'file-earmark-text',
    'lead' => ($bn ? 'সর্বশেষ হালনাগাদ ' : 'Last updated ').$page->updated_at->translatedFormat('d F Y'),
    'crumbs' => [$page->title],
])

<section class="section">
    <div class="container">
        <div class="row g-4">
            {{-- The policies are read as a set — somebody checking the return
                 window usually wants the delivery times next. One page on its
                 own is not a set, and a nav listing only the page you are
                 already on is furniture. --}}
            @php $hasSiblings = $related->count() > 1; @endphp
            @if($hasSiblings)
            <div class="col-lg-4 col-xl-3">
                <aside class="legal-aside">
                    <nav class="legal-nav">
                        <h2>{{ $bn ? 'আরও পড়ুন' : 'More information' }}</h2>
                        <ul>
                            @foreach($related as $item)
                                <li>
                                    <a href="{{ route('pages.show', $item->slug) }}"
                                       class="{{ $item->slug === $page->slug ? 'is-current' : '' }}"
                                       @if($item->slug === $page->slug) aria-current="page" @endif>
                                        <i class="bi bi-{{ $item->slug === $page->slug ? 'record-circle' : 'circle' }}"></i>
                                        <span>{{ $item->title }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>

                    <div class="legal-help">
                        <i class="bi bi-headset"></i>
                        <h3>{{ $bn ? 'কিছু বুঝতে অসুবিধা?' : 'Still not sure?' }}</h3>
                        <p>{{ $bn ? 'আমাদের সাথে কথা বলুন, আমরা বুঝিয়ে দেব।' : 'Talk to us and we will walk you through it.' }}</p>
                        <a href="{{ route('contact') }}" class="btn-primary-custom">
                            <i class="bi bi-chat-dots"></i> {{ $bn ? 'যোগাযোগ করুন' : 'Contact us' }}
                        </a>
                    </div>
                </aside>
            </div>
            @endif

            <div class="{{ $hasSiblings ? 'col-lg-8 col-xl-9' : 'col-lg-10 mx-auto' }}">
                <article class="legal-card">
                    <div class="legal-prose">
                        {!! \App\Support\RichText::display($page->content) !!}
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>
@endsection
