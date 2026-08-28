{{--
    Action bar for a bulk-delete table. Hidden until something is ticked.

    @include('admin.partials.bulk-bar', ['label' => 'Delete selected'])
--}}
<div data-bulk-bar class="d-none align-items-center gap-3 bg-warning-subtle border border-warning-subtle rounded px-3 py-2 mb-3">
    <i class="bi bi-check2-square text-warning"></i>
    <span data-bulk-count class="fw-bold text-dark small"></span>
    <button type="submit" data-bulk-submit class="btn btn-sm btn-danger ms-auto">
        <i class="bi bi-trash"></i> {{ $label ?? 'Delete selected' }}
    </button>
</div>
