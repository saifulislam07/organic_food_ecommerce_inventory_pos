@extends('admin.layouts.app')
@section('page_title', 'Contact Messages')

@section('content')
<div class="admin-toolbar">
    <ul class="nav nav-pills gap-1">
        <li class="nav-item">
            <a class="nav-link {{ $status === 'all' ? 'active' : '' }}"
               href="{{ route('admin.contact-messages.index', ['status' => 'all']) }}">All</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'unread' ? 'active' : '' }}"
               href="{{ route('admin.contact-messages.index', ['status' => 'unread']) }}">
                Unread @if($unreadCount)<span class="badge bg-danger ms-1">{{ $unreadCount }}</span>@endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'read' ? 'active' : '' }}"
               href="{{ route('admin.contact-messages.index', ['status' => 'read']) }}">Read</a>
        </li>
    </ul>
    @include('admin.partials.search', ['route' => route('admin.contact-messages.index'), 'placeholder' => 'Name, phone or message'])
</div>

@can('contact-messages.delete')
<form id="bulk-contact-messages" method="POST" action="{{ route('admin.contact-messages.bulkDestroy') }}"
      data-bulk data-bulk-noun="messages">
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
                        @can('contact-messages.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-contact-messages"></th>@endcan
                        @include('admin.partials.sort', ['key' => 'name', 'label' => 'Name', 'first' => 'asc'])
                        <th>Phone</th>
                        <th>Message</th>
                        <th>Status</th>
                        @include('admin.partials.sort', ['key' => 'created_at', 'label' => 'Received'])
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($messages as $message)
                <tr class="{{ ! $message->is_read ? 'fw-bold' : '' }}">
                    @can('contact-messages.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-contact-messages" name="ids[]" value="{{ $message->id }}"></td>@endcan
                    <td style="padding: 8px 16px;">{{ $message->name }}</td>
                    <td><a href="tel:{{ $message->phone }}" class="{{ ! $message->is_read ? '' : 'text-muted' }}">{{ $message->phone }}</a></td>
                    <td class="small {{ ! $message->is_read ? '' : 'text-muted' }}" style="max-width:360px; white-space: pre-wrap;">
                        {{ $message->message }}
                    </td>
                    <td>
                        @if($message->is_read) <span class="badge bg-secondary fw-normal">Read</span>
                        @else <span class="badge bg-danger fw-normal">Unread</span> @endif
                    </td>
                    <td class="small text-muted fw-normal">{{ $message->created_at->diffForHumans() }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            @can('contact-messages.view')
                            <form action="{{ route('admin.contact-messages.read', $message) }}" method="POST">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary" title="{{ $message->is_read ? 'Mark as unread' : 'Mark as read' }}">
                                    <i class="bi {{ $message->is_read ? 'bi-envelope' : 'bi-envelope-open' }}"></i>
                                </button>
                            </form>
                            @endcan
                            @can('contact-messages.delete')
                            <form action="{{ route('admin.contact-messages.destroy', $message) }}" method="POST" data-confirm="Delete this message?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-envelope d-block mb-2" style="font-size:2rem;"></i>
                        No messages yet.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="admin-pager">{{ $messages->links() }}</div>
@endsection
