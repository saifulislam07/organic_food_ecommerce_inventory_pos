<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') – {{ \App\Models\Setting::get('site_title', 'MohiPure') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @include('partials.favicon')

    <link href="{{ asset('css/brand.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .admin-sidebar {
            width: 260px;
            /* Fixed, so it does not scroll with the page. The menu is taller than
               a laptop viewport, and without its own scrolling the last items are
               simply clipped away with no way to reach them. */
            height: 100vh;
            overflow-y: auto;
            overscroll-behavior: contain;
            background: linear-gradient(180deg, var(--primary-dark), var(--primary-darker));
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1040;
            padding-top: 20px;
            transition: var(--transition);
        }
        /* A thin scrollbar that does not fight the dark sidebar. */
        .admin-sidebar::-webkit-scrollbar { width: 6px; }
        .admin-sidebar::-webkit-scrollbar-track { background: transparent; }
        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.18);
            border-radius: 3px;
        }
        .admin-sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.3); }
        .admin-sidebar { scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.18) transparent; }
        .admin-sidebar .brand {
            padding: 4px 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 16px;
        }
        .admin-sidebar .brand a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .admin-sidebar .brand .brand-logo { height: 36px; }
        .admin-sidebar .brand .highlight { color: var(--accent); }
        .admin-nav { list-style: none; padding: 0 0 24px; margin: 0; }
        .admin-nav li { margin-bottom: 2px; }
        .admin-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }
        .admin-nav a:hover, .admin-nav a.active {
            background: rgba(255,255,255,0.08);
            color: white;
            border-left-color: var(--accent);
        }
        .admin-nav i { font-size: 1.2rem; width: 24px; text-align: center; }
        .admin-nav .nav-divider {
            margin: 12px 24px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        /* Collapsible groups keep nineteen destinations down to nine visible rows. */
        .admin-nav .nav-parent { cursor: pointer; }
        .admin-nav .nav-parent .chevron {
            margin-left: auto;
            font-size: 0.75rem;
            transition: transform 0.2s ease;
            opacity: 0.6;
        }
        .admin-nav .nav-parent[aria-expanded="true"] .chevron { transform: rotate(180deg); }
        .admin-nav .nav-parent[aria-expanded="true"] { color: #fff; }

        .admin-nav .nav-children { list-style: none; padding: 0; margin: 0; }
        .admin-nav .nav-children a {
            padding: 9px 24px 9px 58px;
            font-size: 0.9rem;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.6);
        }
        .admin-nav .nav-children a:hover,
        .admin-nav .nav-children a.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.06);
        }
        /* The dot marks a child row without competing with the parent icons. */
        .admin-nav .nav-children a::before {
            content: '';
            position: absolute;
            left: 34px;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.45;
        }
        .admin-nav .nav-children a { position: relative; }
        .admin-nav .nav-children a.active::before { opacity: 1; }
        .admin-content {
            margin-left: 260px;
            padding: 24px;
            min-height: 100vh;
        }
        .admin-topbar {
            background: white;
            padding: 16px 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-topbar h4 { margin: 0; font-weight: 700; color: var(--primary-dark); }
        .admin-topbar .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gray-500);
        }
        @media (max-width: 991px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show { transform: translateX(0); }
            .admin-content { margin-left: 0; }
        }
    </style>
    @vite(['resources/js/admin.js'])
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="brand">
            <a href="{{ route('admin.dashboard') }}">
                @include('partials.brand', ['size' => 'sm', 'onDark' => true])
            </a>
        </div>
        @php
            // A group opens on load when the page you are on lives inside it.
            $groups = [
                'sell' => [
                    'label' => 'Sell',
                    'icon' => 'bi-cart3',
                    'patterns' => ['admin.pos.*', 'admin.orders.*', 'admin.customers.*'],
                    'items' => [
                        ['route' => 'admin.pos.index', 'active' => 'admin.pos.*', 'label' => 'POS System', 'can' => 'pos.view'],
                        ['route' => 'admin.orders.index', 'active' => 'admin.orders.*', 'label' => 'Orders', 'can' => 'orders.view'],
                        ['route' => 'admin.customers.index', 'active' => 'admin.customers.*', 'label' => 'Customers', 'can' => 'customers.view'],
                    ],
                ],
                'catalogue' => [
                    'label' => 'Catalogue',
                    'icon' => 'bi-box-seam',
                    'patterns' => ['admin.products.*', 'admin.categories.*', 'admin.units.*'],
                    'items' => [
                        ['route' => 'admin.products.index', 'active' => 'admin.products.*', 'label' => 'Products', 'can' => 'products.view'],
                        ['route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'label' => 'Categories', 'can' => 'categories.view'],
                        ['route' => 'admin.combos.index', 'active' => 'admin.combos.*', 'label' => 'Combos', 'can' => 'combos.view'],
                        ['route' => 'admin.units.index', 'active' => 'admin.units.*', 'label' => 'Units', 'can' => 'units.view'],
                    ],
                ],
                'stock' => [
                    'label' => 'Stock',
                    'icon' => 'bi-clipboard-data',
                    'patterns' => ['admin.inventory.*', 'admin.purchases.*', 'admin.suppliers.*', 'admin.adjustments.*'],
                    'items' => [
                        ['route' => 'admin.inventory.index', 'active' => 'admin.inventory.*', 'label' => 'Inventory', 'can' => 'inventory.view'],
                        ['route' => 'admin.purchases.index', 'active' => 'admin.purchases.*', 'label' => 'Purchases', 'can' => 'purchases.view'],
                        ['route' => 'admin.suppliers.index', 'active' => 'admin.suppliers.*', 'label' => 'Suppliers', 'can' => 'suppliers.view'],
                        ['route' => 'admin.adjustments.index', 'active' => 'admin.adjustments.*', 'label' => 'Adjustments', 'can' => 'adjustments.view'],
                    ],
                ],
                'settings' => [
                    'label' => 'Settings',
                    'icon' => 'bi-gear',
                    'patterns' => ['admin.settings.*'],
                    'items' => [
                        ['route' => 'admin.settings.index', 'active' => 'admin.settings.index', 'label' => 'Site Settings', 'can' => 'settings.view'],
                        ['route' => 'admin.settings.mail.edit', 'active' => 'admin.settings.mail.*', 'label' => 'Email / SMTP', 'can' => 'settings.edit'],
                        ['route' => 'admin.settings.sms.edit', 'active' => 'admin.settings.sms.*', 'label' => 'SMS Gateway', 'can' => 'settings.edit'],
                        ['route' => 'admin.settings.seo.edit', 'active' => 'admin.settings.seo.*', 'label' => 'SEO & Analytics', 'can' => 'settings.edit'],
                        ['route' => 'admin.settings.chat.edit', 'active' => 'admin.settings.chat.*', 'label' => 'WhatsApp & Messenger', 'can' => 'settings.edit'],
                        ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'label' => 'Users', 'can' => 'users.view'],
                        ['route' => 'admin.roles.index', 'active' => 'admin.roles.*', 'label' => 'Roles', 'can' => 'roles.view'],
                    ],
                ],
            ];
        @endphp

        <ul class="admin-nav">
            <li>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>

            @foreach($groups as $key => $group)
                @php
                    // Hide anything this user has no view permission for, and drop
                    // a whole group once nothing inside it is left.
                    $items = collect($group['items'])
                        ->filter(fn ($item) => ! isset($item['can']) || auth()->user()->can($item['can']))
                        ->values();
                @endphp
                @continue($items->isEmpty())
                @php $open = request()->routeIs(...$group['patterns']); @endphp
                <li>
                    <a href="#nav-{{ $key }}" class="nav-parent {{ $open ? 'active' : '' }}"
                       data-bs-toggle="collapse" role="button"
                       aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="nav-{{ $key }}">
                        <i class="bi {{ $group['icon'] }}"></i> {{ $group['label'] }}
                        <i class="bi bi-chevron-down chevron"></i>
                    </a>
                    <ul class="collapse nav-children {{ $open ? 'show' : '' }}" id="nav-{{ $key }}">
                        @foreach($items as $item)
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   class="{{ request()->routeIs($item['active']) ? 'active' : '' }}">
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endforeach

            @can('expenses.view')
            <li>
                <a href="{{ route('admin.expenses.index') }}" class="{{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}">
                    <i class="bi bi-cash-stack"></i> Expenses
                </a>
            </li>
            @endcan
            @can('reports.view')
            <li>
                <a href="{{ route('admin.reports.profitLoss') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i class="bi bi-graph-up-arrow"></i> Profit &amp; Loss
                </a>
            </li>
            @endcan
            @can('pages.view')
            <li>
                <a href="{{ route('admin.pages.index') }}" class="{{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-text"></i> Static Pages
                </a>
            </li>
            @endcan

            <div class="nav-divider"></div>
            <li>
                <a href="{{ route('home') }}" target="_blank">
                    <i class="bi bi-globe"></i> View Site
                </a>
            </li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="#" onclick="event.preventDefault(); this.closest('form').submit();">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </form>
            </li>
        </ul>
    </aside>

    <!-- Content -->
    <div class="admin-content">
        <div class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" onclick="document.getElementById('adminSidebar').classList.toggle('show')">
                    <i class="bi bi-list"></i>
                </button>
                <h4>@yield('page_title', 'Dashboard')</h4>
            </div>
            <div class="user-info">
                @php $unread = auth()->user()->unreadNotifications()->count(); @endphp
                <a href="{{ route('admin.notifications.index') }}"
                   class="position-relative text-decoration-none me-3"
                   title="{{ $unread ? $unread.' unread notification(s)' : 'Notifications' }}">
                    <i class="bi {{ $unread ? 'bi-bell-fill text-primary' : 'bi-bell' }}" style="font-size:1.35rem;"></i>
                    @if($unread)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                              style="font-size:.65rem;">{{ $unread > 99 ? '99+' : $unread }}</span>
                    @endif
                </a>
                <i class="bi bi-person-circle" style="font-size:1.5rem;"></i>
                <span>{{ auth()->user()->name }}</span>
            </div>
        </div>

        @php
            $adminFlash = [
                'success' => session('success'),
                'error' => session('error'),
                'errors' => $errors->all(),
            ];
        @endphp
        <script type="application/json" id="admin-flash">{!! json_encode($adminFlash, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
        <div data-vue="AdminDialogs"></div>

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
