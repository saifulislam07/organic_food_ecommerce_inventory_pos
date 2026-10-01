{{-- Block / unblock toggle. $compact renders an icon-only button for table rows. --}}
@if($customer->isBlocked())
    <form action="{{ route('admin.customers.toggleBlock', $customer) }}" method="POST" class="d-inline"
          data-confirm="Unblock {{ $customer->name }}? They will be able to sign in again." data-confirm-label="Unblock">
        @csrf
        <button class="btn btn-sm btn-outline-success" title="Unblock">
            <i class="bi bi-unlock"></i>@unless($compact) Unblock customer @endunless
        </button>
    </form>
@else
    <form action="{{ route('admin.customers.toggleBlock', $customer) }}" method="POST" class="d-inline"
          data-confirm="Block {{ $customer->name }}? They will be signed out and unable to sign in." data-confirm-label="Block">
        @csrf
        <button class="btn btn-sm btn-outline-danger" title="Block">
            <i class="bi bi-slash-circle"></i>@unless($compact) Block customer @endunless
        </button>
    </form>
@endif
