@extends('admin.layouts.app')
@section('page_title', 'Storefront Blocks')

@section('content')
@php $groups = \App\Models\SiteBlock::GROUPS; @endphp

{{-- One tab per list. The tab is the filter, so ?group travels with the search,
     the Add button and every redirect back from the form. --}}
<ul class="nav nav-pills flex-wrap gap-2 mb-4">
    @foreach($groups as $key => $definition)
        <li class="nav-item">
            <a class="nav-link {{ $group === $key ? 'active' : '' }}"
               href="{{ route('admin.blocks.index', ['group' => $key]) }}">
                {{ $definition['label'] }}
            </a>
        </li>
    @endforeach
</ul>

<div class="admin-toolbar">
    <h6 class="admin-toolbar__title">
        {{ $groups[$group]['label'] }}
        <span class="admin-toolbar__count">{{ number_format($blocks->total()) }}</span>
    </h6>
    @include('admin.partials.search', [
        'route' => route('admin.blocks.index', ['group' => $group]),
        'placeholder' => 'Item title',
    ])
    <a href="{{ route('admin.blocks.create', ['group' => $group]) }}" class="btn btn-success flex-shrink-0">
        <i class="bi bi-plus-circle"></i> Add Item
    </a>
</div>

<p class="text-muted small">{{ $groups[$group]['hint'] }}</p>

@can('blocks.delete')
<form id="bulk-blocks" method="POST" action="{{ route('admin.blocks.bulkDestroy') }}"
      data-bulk data-bulk-noun="items">
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
                        @can('blocks.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-blocks"></th>@endcan
                        @include('admin.partials.sort', ['key' => 'title', 'label' => 'Title', 'first' => 'asc'])
                        @if(\App\Models\SiteBlock::groupHasField($group, 'url'))<th>Link</th>@endif
                        @include('admin.partials.sort', ['key' => 'status', 'label' => 'Status'])
                        @include('admin.partials.sort', ['key' => 'sort_order', 'label' => 'Sort Order', 'first' => 'asc'])
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($blocks as $block)
                <tr>
                    @can('blocks.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-blocks" name="ids[]" value="{{ $block->id }}"></td>@endcan
                    <td style="padding: 12px 16px;">
                        <div class="d-flex align-items-center gap-2">
                            @if($block->image_url)
                                <img src="{{ $block->image_url }}" alt="" style="width:48px; height:32px; object-fit:cover; border-radius:6px;">
                            @elseif(\App\Models\SiteBlock::groupHasField($group, 'icon'))
                                <i class="bi bi-{{ $block->icon_name }} text-success" style="font-size:1.2rem;"></i>
                            @endif
                            <div>
                                <strong>{{ $block->title_en }}</strong>
                                @if($block->is_highlighted)
                                    <span class="badge bg-warning text-dark ms-1">Highlighted</span>
                                @endif
                                @if($block->title_bn)<div class="text-muted small">{{ $block->title_bn }}</div>@endif
                                @if($block->subtitle_en)<div class="text-muted small">{{ $block->subtitle_en }}</div>@endif
                            </div>
                        </div>
                    </td>
                    @if(\App\Models\SiteBlock::groupHasField($group, 'url'))
                        <td class="small text-muted">{{ $block->url ?: 'Home page' }}</td>
                    @endif
                    <td>
                        @if($block->is_active) <span class="badge bg-success">Active</span>
                        @else <span class="badge bg-secondary">Inactive</span> @endif
                    </td>
                    <td>{{ $block->sort_order }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.blocks.edit', $block) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.blocks.destroy', $block) }}" method="POST" data-confirm="Delete this item?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-collection d-block mb-2" style="font-size:2rem;"></i>
                        এই তালিকায় এখনো কিছু যোগ করা হয়নি।
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="admin-pager">{{ $blocks->links() }}</div>
@endsection
