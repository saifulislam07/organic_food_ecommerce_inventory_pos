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
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4">
                    <div class="page-content" style="line-height: 1.8; color: var(--gray-700);">
                        {!! nl2br(e($page->content)) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
