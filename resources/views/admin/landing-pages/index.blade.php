@extends('admin.layouts.app')
@section('page_title', 'Landing Pages')

@section('content')
<div class="d-flex mb-3">
    @include('admin.partials.search', ['route' => route('admin.landing-pages.index'), 'placeholder' => 'নাম, URL বা হেডলাইন'])
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1">ক্যাম্পেইন ল্যান্ডিং পেজ</h5>
        <p class="text-muted small mb-0">
            ফেসবুক বুস্টের জন্য আলাদা পেজ — নিজস্ব অফার, নিজস্ব লিংক, কার্ট ছাড়া সরাসরি অর্ডার।
        </p>
    </div>
    @can('landing-pages.create')
    <a href="{{ route('admin.landing-pages.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> নতুন পেজ
    </a>
    @endcan
</div>

@can('landing-pages.delete')
<form id="bulk-landing-pages" method="POST" action="{{ route('admin.landing-pages.bulkDestroy') }}"
      data-bulk data-bulk-noun="landing pages">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan

<div class="card admin-card p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background: var(--gray-100);">
                <tr>
                    @can('landing-pages.delete')<th style="width:38px;" class="px-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-landing-pages"></th>@endcan
                    <th class="px-4 py-3">পেজ</th>
                    <th>স্ট্যাটাস</th>
                    <th class="text-end">ভিউ</th>
                    <th class="text-end">অর্ডার</th>
                    <th class="text-end">কনভার্শন</th>
                    <th class="text-end">বিক্রি</th>
                    <th class="text-end px-4">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pages as $page)
                    @php
                        $rate = $page->views > 0 ? ($page->orders_count / $page->views) * 100 : null;
                    @endphp
                    <tr>
                        @can('landing-pages.delete')<td class="px-4"><input type="checkbox" class="form-check-input" form="bulk-landing-pages" name="ids[]" value="{{ $page->id }}"></td>@endcan
                        <td class="px-4">
                            <span class="fw-bold">{{ $page->internal_name }}</span><br>
                            <small class="text-muted d-inline-flex align-items-center gap-2">
                                <code>/{{ config('landing.prefix', 'lp') }}/{{ $page->slug }}</code>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none"
                                        data-copy="{{ $page->url() }}" title="লিংক কপি করুন">
                                    <i class="bi bi-link-45deg"></i>
                                </button>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none"
                                        data-copy="{{ $page->adUrl() }}" title="UTM সহ অ্যাড লিংক কপি করুন">
                                    <i class="bi bi-megaphone"></i> Ad URL
                                </button>
                            </small>
                        </td>
                        <td>
                            @if ($page->isRunning())
                                <span class="badge bg-success">লাইভ</span>
                            @elseif (! $page->is_active)
                                <span class="badge bg-secondary">ড্রাফট</span>
                            @elseif ($page->starts_at?->isFuture())
                                <span class="badge bg-info">শুরু হয়নি</span>
                            @else
                                <span class="badge bg-danger">মেয়াদ শেষ</span>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format($page->views) }}</td>
                        <td class="text-end fw-bold">{{ number_format($page->orders_count) }}</td>
                        <td class="text-end">
                            @if ($rate === null)
                                <span class="text-muted">—</span>
                            @else
                                <span class="badge {{ $rate >= 2 ? 'bg-success-subtle text-success' : 'bg-light text-muted' }}">
                                    {{ number_format($rate, 1) }}%
                                </span>
                            @endif
                        </td>
                        <td class="text-end">৳{{ number_format((float) $page->revenue) }}</td>
                        <td class="text-end px-4">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ $page->url() }}" target="_blank" rel="noopener"
                                   class="btn btn-outline-secondary" title="পেজ দেখুন">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('landing-pages.edit')
                                <a href="{{ route('admin.landing-pages.edit', $page) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-pencil"></i> এডিট
                                </a>
                                @endcan
                                @can('landing-pages.create')
                                <form action="{{ route('admin.landing-pages.duplicate', $page) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-outline-success" title="কপি করে নতুন পেজ">
                                        <i class="bi bi-files"></i>
                                    </button>
                                </form>
                                @endcan
                                @can('landing-pages.delete')
                                <form action="{{ route('admin.landing-pages.destroy', $page) }}" method="POST" class="d-inline"
                                      data-confirm="&quot;{{ $page->internal_name }}&quot; পেজটি মুছে ফেলবেন?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()?->can('landing-pages.delete') ? 8 : 7 }}" class="text-center py-5 text-muted">
                            এখনো কোনো ল্যান্ডিং পেজ নেই।
                            @can('landing-pages.create')
                                <a href="{{ route('admin.landing-pages.create') }}">প্রথমটি তৈরি করুন</a>।
                            @endcan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($pages->hasPages())
    <div class="mt-3">{{ $pages->links() }}</div>
@endif
@endsection
