<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') – Mango Hut</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .admin-sidebar {
            width: 260px;
            min-height: 100vh;
            background: linear-gradient(180deg, #1b4332, #0d1b2a);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1040;
            padding-top: 20px;
            transition: var(--transition);
        }
        .admin-sidebar .brand {
            padding: 0 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 16px;
        }
        .admin-sidebar .brand a {
            color: white;
            text-decoration: none;
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .admin-sidebar .brand .highlight { color: var(--accent); }
        .admin-nav { list-style: none; padding: 0; margin: 0; }
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
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="brand">
            <a href="{{ route('admin.dashboard') }}">🥭 Mango<span class="highlight">Hut</span></a>
        </div>
        <ul class="admin-nav">
            <li>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <div class="nav-divider"></div>
            <li>
                <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam"></i> Products
                </a>
            </li>
            <li>
                <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <i class="bi bi-tags"></i> Categories
                </a>
            </li>
            <li>
                <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <i class="bi bi-receipt"></i> Orders
                </a>
            </li>
            <li>
                <a href="{{ route('admin.pos.index') }}" class="{{ request()->routeIs('admin.pos.*') ? 'active' : '' }}">
                    <i class="bi bi-calculator"></i> POS System
                </a>
            </li>
            <li>
                <a href="{{ route('admin.inventory.index') }}" class="{{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam"></i> Inventory
                </a>
            </li>
            <li>
                <a href="{{ route('admin.suppliers.index') }}" class="{{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}">
                    <i class="bi bi-truck"></i> Suppliers
                </a>
            </li>
            <li>
                <a href="{{ route('admin.purchases.index') }}" class="{{ request()->routeIs('admin.purchases.*') ? 'active' : '' }}">
                    <i class="bi bi-cart-check"></i> Purchases
                </a>
            </li>
            <li>
                <a href="{{ route('admin.adjustments.index') }}" class="{{ request()->routeIs('admin.adjustments.*') ? 'active' : '' }}">
                    <i class="bi bi-tools"></i> Adjustments
                </a>
            </li>
            <li>
                <a href="{{ route('admin.expenses.index') }}" class="{{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}">
                    <i class="bi bi-cash-stack"></i> Expenses
                </a>
            </li>
            <div class="nav-divider"></div>
            <li>
                <a href="{{ route('admin.pages.index') }}" class="{{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-text"></i> Static Pages
                </a>
            </li>
            <li>
                <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i class="bi bi-gear"></i> Site Settings
                </a>
            </li>
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
                <i class="bi bi-person-circle" style="font-size:1.5rem;"></i>
                <span>{{ auth()->user()->name }}</span>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    @stack('scripts')
</body>
</html>
