{{--
    Shared search box for admin tables. Belongs inside an .admin-toolbar, which
    is where it gets its sizing.

    @include('admin.partials.search', ['route' => route('admin.units.index'), 'placeholder' => 'Name or code'])
--}}
<form action="{{ $route }}" method="GET" class="admin-toolbar__search ms-auto">
    {{-- Filters, tabs and the sort column all live in the query string, so they
         have to ride along or searching would quietly reset them. --}}
    @foreach(request()->except(['search', 'page']) as $key => $value)
        @if(! is_array($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="search" name="search"
               class="form-control @if(request('search')) is-filtering @endif"
               value="{{ request('search') }}"
               placeholder="{{ $placeholder ?? 'Search…' }}">
        @if(request('search'))
            <a href="{{ $route }}" class="btn btn-outline-secondary" title="Clear search">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </div>
</form>
