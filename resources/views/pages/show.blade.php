@extends('layouts.frontend')

@section('title', $page->title . ' – ' . \App\Models\Setting::get('site_title', 'Mango Hut'))

@section('content')
<div class="page-header py-5" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white;">
    <div class="container text-center">
        <h1 class="fw-bold mb-0">{{ $page->title }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0 mt-3">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-white-50 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">{{ $page->title }}</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white position-relative overflow-hidden">
                    <!-- Decorative Corner -->
                    <div class="position-absolute top-0 end-0 opacity-05" style="transform: translate(25%, -25%);">
                        <span style="font-size: 15rem;">🍃</span>
                    </div>

                    <div class="page-content position-relative z-index-1" style="line-height: 1.8; color: var(--dark); font-size: 1.05rem;">
                        {!! $page->content !!}
                    </div>

                    <div class="mt-5 pt-4 border-top">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="text-muted small italic">
                                {{ app()->getLocale() == 'bn' ? 'শেষ আপডেট:' : 'Last updated:' }} {{ $page->updated_at->format('M d, Y') }}
                            </div>
                            <a href="{{ route('contact') }}" class="btn btn-outline-primary btn-sm rounded-pill px-4">
                                {{ app()->getLocale() == 'bn' ? 'প্রশ্ন আছে? যোগাযোগ করুন' : 'Have questions? Contact us' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
