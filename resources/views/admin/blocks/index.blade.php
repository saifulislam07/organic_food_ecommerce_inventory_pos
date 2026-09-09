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

<div class="d-flex mb-3">
    @include('admin.partials.search', [
        'route' => route('admin.blocks.index', ['group' => $group]),
        'placeholder' => 'Item title',
    ])
</div>

<div class="d-flex justify-content-between align-items-center mb-4 gap-3">
    <p class="text-muted small mb-0">{{ $groups[$group]['hint'] }}</p>
    <a href="{{ route('admin.blocks.create', ['group' => $group]) }}" class="btn btn-success flex-shrink-0">
        <i class="bi bi-plus-circle"></i> Add Item
    </a>
</div>

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
            <table class="table table-hover mb-0">
                <thead style="background: var(--gray-100);">
                    <tr>
                        @can('blocks.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-blocks"></th>@endcan
                        <th style="padding: 14px 16px;">Title</th>
                        @if(\App\Models\SiteBlock::groupHasField($group, 'url'))<th>Link</th>@endif
                        <th>Status</th>
                        <th>Sort Order</th>
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
<div class="mt-3">{{ $blocks->links() }}</div>
@endsection
