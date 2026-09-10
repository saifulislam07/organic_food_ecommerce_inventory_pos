{{--
    A clickable column header for an admin table.

    @include('admin.partials.sort', ['key' => 'total', 'label' => 'Total', 'class' => 'text-end'])

    'key' must be one the controller whitelisted in applySort(); anything else
    silently sorts by the default. $adminSort is shared by that same call.

    'first' is the direction a fresh click takes: dates and amounts want their
    biggest value first, names want A–Z.
--}}
@php
    $state = $adminSort ?? ['key' => null, 'dir' => 'desc'];
    $isActive = $state['key'] === $key;
    $firstDir = ($first ?? 'desc') === 'asc' ? 'asc' : 'desc';
    $nextDir = $isActive
        ? ($state['dir'] === 'asc' ? 'desc' : 'asc')
        : $firstDir;
@endphp
<th class="admin-th-sort {{ $isActive ? 'is-sorted' : '' }} {{ $class ?? '' }}"
    aria-sort="{{ $isActive ? ($state['dir'] === 'asc' ? 'ascending' : 'descending') : 'none' }}">
    <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'dir' => $nextDir, 'page' => null]) }}"
       title="Sort by {{ $label }} ({{ $nextDir === 'asc' ? 'ascending' : 'descending' }})">
        <span>{{ $label }}</span>
        <i class="bi {{ $isActive ? ($state['dir'] === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-chevron-expand' }}"></i>
    </a>
</th>
