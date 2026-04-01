@extends('layouts.frontend')

@section('title', (app()->getLocale() == 'bn' ? 'আমার অ্যাকাউন্ট' : 'My Account') . ' – Mango Hut')

@push('styles')
<style>
    .dashboard-wrapper {
        padding: 60px 0;
        background-color: #f8f9fa;
    }
    .dashboard-sidebar {
        background: white;
        border-radius: var(--radius-lg);
        padding: 20px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-100);
        height: fit-content;
    }
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        border-radius: var(--radius-sm);
        color: var(--gray-600);
        text-decoration: none;
        transition: var(--transition);
        font-weight: 600;
        margin-bottom: 5px;
    }
    .sidebar-link i {
        font-size: 1.2rem;
    }
    .sidebar-link:hover, .sidebar-link.active {
        background-color: rgba(45, 106, 79, 0.05);
        color: var(--primary);
    }
    .sidebar-link.text-danger:hover {
        background-color: #fff5f5;
        color: #dc3545;
    }

    .stat-card {
        background: white;
        border-radius: var(--radius-lg);
        padding: 25px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-100);
        transition: var(--transition);
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-md);
    }
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .order-table th {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--gray-500);
        font-weight: 700;
        padding: 15px 20px;
        background-color: var(--gray-100);
    }
    .order-table td {
        padding: 15px 20px;
        vertical-align: middle;
    }
    .status-badge {
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    
    .page-header-simple {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
        padding: 40px 0;
        color: white;
        margin-bottom: 40px;
        border-radius: 0 0 40px 40px;
    }
</style>
@endpush

@section('content')
<div class="page-header-simple text-center">
    <div class="container">
        <h2 class="fw-bold mb-0">{{ app()->getLocale() == 'bn' ? 'আমার অ্যাকাউন্ট' : 'My Account' }}</h2>
        <p class="mb-0 text-white-50">{{ app()->getLocale() == 'bn' ? 'স্বাগতম' : 'Welcome back' }}, {{ auth()->user()->name }}!</p>
    </div>
</div>

<div class="dashboard-wrapper">
    <div class="container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="dashboard-sidebar">
                    <div class="text-center mb-4 pb-4 border-bottom">
                        <div class="mb-2">
                            <div class="avatar-placeholder bg-primary-light d-inline-flex align-items-center justify-content-center rounded-circle text-primary fw-bold fs-3" style="width: 70px; height: 70px;">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        </div>
                        <h5 class="mb-0 fw-bold">{{ auth()->user()->name }}</h5>
                        <small class="text-muted">{{ auth()->user()->email }}</small>
                    </div>
                    
                    <nav>
                        <a href="{{ route('customer.dashboard') }}" class="sidebar-link active">
                            <i class="bi bi-speedometer2"></i> {{ app()->getLocale() == 'bn' ? 'ড্যাশবোর্ড' : 'Dashboard' }}
                        </a>
                        <a href="{{ route('cart.index') }}" class="sidebar-link">
                            <i class="bi bi-cart"></i> {{ app()->getLocale() == 'bn' ? 'আমার কার্ট' : 'My Cart' }}
                        </a>
                        <a href="{{ route('profile.edit') }}" class="sidebar-link">
                            <i class="bi bi-person-gear"></i> {{ app()->getLocale() == 'bn' ? 'প্রোফাইল সেটিংস' : 'Profile Settings' }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="sidebar-link text-danger border-0 bg-transparent w-100">
                                <i class="bi bi-box-arrow-right"></i> {{ app()->getLocale() == 'bn' ? 'লগআউট' : 'Logout' }}
                            </button>
                        </form>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Stats -->
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-success-light text-success">
                                <i class="bi bi-bag-check"></i>
                            </div>
                            <div>
                                <h3 class="mb-0 fw-bold">{{ $orders->total() }}</h3>
                                <small class="text-muted text-uppercase tracking-wider fw-bold" style="font-size: 0.75rem;">{{ app()->getLocale() == 'bn' ? 'মোট অর্ডার' : 'Total Orders' }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning-light text-warning">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <div>
                                <h3 class="mb-0 fw-bold">{{ auth()->user()->orders()->where('status', 'pending')->count() }}</h3>
                                <small class="text-muted text-uppercase tracking-wider fw-bold" style="font-size: 0.75rem;">{{ app()->getLocale() == 'bn' ? 'অপেক্ষমান' : 'Pending' }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon bg-info-light text-info">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div>
                                <h3 class="mb-0 fw-bold">৳{{ number_format(auth()->user()->orders()->sum('total')) }}</h3>
                                <small class="text-muted text-uppercase tracking-wider fw-bold" style="font-size: 0.75rem;">{{ app()->getLocale() == 'bn' ? 'মোট খরচ' : 'Total Spent' }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="admin-card overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-receipt text-primary"></i> {{ app()->getLocale() == 'bn' ? 'সাম্প্রতিক অর্ডারসমূহ' : 'Recent Orders' }}</h5>
                        <a href="{{ route('shop') }}" class="btn btn-sm btn-outline-primary fw-bold">{{ app()->getLocale() == 'bn' ? 'কেনাকাটা করুন' : 'Shop More' }}</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table order-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ app()->getLocale() == 'bn' ? 'অর্ডার নং' : 'Order' }}</th>
                                    <th>{{ app()->getLocale() == 'bn' ? 'তারিখ' : 'Date' }}</th>
                                    <th>{{ app()->getLocale() == 'bn' ? 'অবস্থা' : 'Status' }}</th>
                                    <th>{{ app()->getLocale() == 'bn' ? 'মোট' : 'Total' }}</th>
                                    <th class="text-center">{{ app()->getLocale() == 'bn' ? 'অ্যাকশন' : 'Action' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                <tr>
                                    <td><span class="fw-bold">#{{ $order->order_number }}</span></td>
                                    <td>{{ $order->created_at->format('d M, Y') }}</td>
                                    <td>
                                        @php
                                            $statusLabel = $order->status;
                                            $badgeStyle = match($order->status) {
                                                'pending' => 'background: #fff8eb; color: #b45309;',
                                                'confirmed' => 'background: #eff6ff; color: #1d4ed8;',
                                                'processing' => 'background: #f5f3ff; color: #6d28d9;',
                                                'shipped' => 'background: #fdf2f8; color: #be185d;',
                                                'delivered' => 'background: #f0fdf4; color: #15803d;',
                                                'cancelled' => 'background: #fff1f2; color: #be123c;',
                                                default => 'background: #f9fafb; color: #374151;',
                                            };

                                            if (app()->getLocale() == 'bn') {
                                                $statusLabel = match($order->status) {
                                                    'pending' => 'পেন্ডিং',
                                                    'confirmed' => 'নিশ্চিত',
                                                    'processing' => 'প্রসেসিং',
                                                    'shipped' => 'শিফট',
                                                    'delivered' => 'ডেলিভারড',
                                                    'cancelled' => 'বাতিল',
                                                    default => $order->status,
                                                };
                                            }
                                        @endphp
                                        <span class="status-badge" style="{{ $badgeStyle }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td><span class="fw-bold">৳{{ number_format($order->total) }}</span></td>
                                    <td class="text-center">
                                        <a href="{{ route('customer.orders.show', $order->order_number) }}" class="btn btn-sm btn-primary px-3 fw-bold">
                                            <i class="bi bi-eye"></i> {{ app()->getLocale() == 'bn' ? 'দেখুন' : 'View' }}
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-5 text-center">
                                        <div class="mb-3"><i class="bi bi-bag-x text-muted fs-1"></i></div>
                                        <p class="text-muted">{{ app()->getLocale() == 'bn' ? 'এখনো কোনো অর্ডার করা হয়নি।' : 'No orders placed yet.' }}</p>
                                        <a href="{{ route('shop') }}" class="btn btn-primary fw-bold">{{ app()->getLocale() == 'bn' ? 'এখনই কিনুন' : 'Start Shopping' }}</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($orders->hasPages())
                    <div class="p-3 border-top">
                        {{ $orders->links('pagination::bootstrap-5') }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
