@extends('admin.layouts.app')
@section('page_title', 'Hero Slider')

@section('content')
<p class="text-muted small">
    হোমপেজের উপরের স্লাইডার। একাধিক স্লাইড থাকলে নিজে থেকেই ঘুরবে;
    কোনো স্লাইড না থাকলে Site Settings-এর হিরো লেখাই দেখানো হবে।
</p>
<div class="admin-toolbar">
    <h6 class="admin-toolbar__title">Slides <span class="admin-toolbar__count">{{ number_format($slides->total()) }}</span></h6>
    @include('admin.partials.search', ['route' => route('admin.sliders.index'), 'placeholder' => 'Slide title'])
    <a href="{{ route('admin.sliders.create') }}" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add Slide</a>
</div>

@can('sliders.delete')
<form id="bulk-sliders" method="POST" action="{{ route('admin.sliders.bulkDestroy') }}"
      data-bulk data-bulk-noun="slides">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan
<div class="card admin-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover admin-table">
                <thead>
                    <tr>
                        @can('sliders.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-sliders"></th>@endcan
                        <th>Image</th>
                        @include('admin.partials.sort', ['key' => 'title', 'label' => 'Title', 'first' => 'asc'])
                        <th>Button</th>
                        @include('admin.partials.sort', ['key' => 'status', 'label' => 'Status'])
                        @include('admin.partials.sort', ['key' => 'sort_order', 'label' => 'Sort Order', 'first' => 'asc'])
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($slides as $slide)
                <tr>
                    @can('sliders.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-sliders" name="ids[]" value="{{ $slide->id }}"></td>@endcan
                    <td style="padding: 8px 16px;">
                        <img src="{{ $slide->image_url }}" alt="" style="width:80px; height:50px; object-fit:cover; border-radius:8px;">
                    </td>
                    <td>
                        <strong>{{ strip_tags($slide->title_en) }}</strong>
                        @if($slide->title_bn)<div class="text-muted small">{{ strip_tags($slide->title_bn) }}</div>@endif
                    </td>
                    <td class="small text-muted">{{ $slide->button_url ?: 'Shop page' }}</td>
                    <td>
                        @if($slide->is_active) <span class="badge bg-success">Active</span>
                        @else <span class="badge bg-secondary">Inactive</span> @endif
                    </td>
                    <td>{{ $slide->sort_order }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.sliders.edit', $slide) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.sliders.destroy', $slide) }}" method="POST" data-confirm="Delete this slide?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-images d-block mb-2" style="font-size:2rem;"></i>
                        এখনো কোনো স্লাইড যোগ করা হয়নি।
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="admin-pager">{{ $slides->links() }}</div>
@endsection
