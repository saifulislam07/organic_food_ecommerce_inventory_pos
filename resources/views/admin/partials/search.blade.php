{{--
    Shared search box for admin tables.

    @include('admin.partials.search', ['route' => route('admin.units.index'), 'placeholder' => 'Name or code'])
--}}
<form action="{{ $route }}" method="GET" class="d-flex gap-2 ms-auto" style="max-width:320px;">
    @foreach(request()->except(['search', 'page']) as $key => $value)
        @if(! is_array($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <div class="input-group input-group-sm">
        <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
        <input type="search" name="search" class="form-control bg-light border-0"
               value="{{ request('search') }}"
               placeholder="{{ $placeholder ?? 'Search…' }}">
        @if(request('search'))
            <a href="{{ $route }}" class="btn btn-outline-secondary" title="Clear">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </div>
</form>
