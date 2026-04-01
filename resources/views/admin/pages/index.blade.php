@extends('admin.layouts.app')
@section('page_title', 'Static Pages')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Manage Pages</h5>
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Create New Page
    </a>
</div>

<div class="card admin-card p-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead style="background: var(--gray-100);">
                <tr>
                    <th class="px-4 py-3">Title (EN)</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th class="text-end px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pages as $page)
                <tr>
                    <td class="px-4">
                        <span class="fw-bold">{{ $page->title_en }}</span><br>
                        <small class="text-muted">{{ $page->title_bn }}</small>
                    </td>
                    <td><code>/p/{{ $page->slug }}</code></td>
                    <td>
                        @if($page->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end px-4">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
