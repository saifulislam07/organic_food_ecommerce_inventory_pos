@extends('admin.layouts.app')
@section('page_title', 'Static Pages')

@section('content')
<div class="admin-toolbar">
    <h6 class="admin-toolbar__title">Manage Pages <span class="admin-toolbar__count">{{ number_format($pages->total()) }}</span></h6>
    @include('admin.partials.search', ['route' => route('admin.pages.index'), 'placeholder' => 'Slug or title'])
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Create New Page
    </a>
</div>

@can('pages.delete')
<form id="bulk-pages" method="POST" action="{{ route('admin.pages.bulkDestroy') }}"
      data-bulk data-bulk-noun="pages">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan
    <div class="card admin-card p-0">
        <div class="table-responsive">
            <table class="table table-hover admin-table">
                <thead>
                    <tr>
                        @can('pages.delete')<th style="width:38px;" class="px-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-pages"></th>@endcan
                        @include('admin.partials.sort', ['key' => 'title', 'label' => 'Title (EN)', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'slug', 'label' => 'Slug', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'status', 'label' => 'Status'])
                        <th class="text-end px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr>
                            @can('pages.delete')<td class="px-4"><input type="checkbox" class="form-check-input" form="bulk-pages" name="ids[]" value="{{ $page->id }}"></td>@endcan
                            <td class="px-4">
                                <span class="fw-bold">{{ $page->title_en }}</span><br>
                                <small class="text-muted">{{ $page->title_bn }}</small>
                            </td>
                            <td><code>{{ $page->slug }}</code></td>
                            <td>
                                @if ($page->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end px-4">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    @can('pages.delete')
                                    <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" class="d-inline"
                                          data-confirm="Delete the &quot;{{ $page->title_en }}&quot; page?">
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
                            <td colspan="{{ auth()->user()?->can('pages.delete') ? 5 : 4 }}" class="text-center py-5 text-muted">
                                No pages yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@if($pages->hasPages())
    <div class="admin-pager">{{ $pages->links() }}</div>
@endif

@endsection
