@extends('layouts.frontend')

@section('title', (app()->getLocale() == 'bn' ? 'প্রোফাইল সেটিংস' : 'Profile Settings') . ' – Mango Hut')

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

    .profile-section {
        background: white;
        border-radius: var(--radius-lg);
        padding: 30px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-100);
        margin-bottom: 30px;
    }
    
    .profile-section-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
</style>
@endpush

@section('content')
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
                        <a href="{{ route('customer.dashboard') }}" class="sidebar-link">
                            <i class="bi bi-speedometer2"></i> {{ app()->getLocale() == 'bn' ? 'ড্যাশবোর্ড' : 'Dashboard' }}
                        </a>
                        <a href="{{ route('cart.index') }}" class="sidebar-link">
                            <i class="bi bi-cart"></i> {{ app()->getLocale() == 'bn' ? 'আমার কার্ট' : 'My Cart' }}
                        </a>
                        <a href="{{ route('profile.edit') }}" class="sidebar-link active">
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
                <div class="profile-section">
                    <div class="profile-section-title">
                        <i class="bi bi-person-circle text-primary"></i>
                        {{ app()->getLocale() == 'bn' ? 'ব্যক্তিগত তথ্য' : 'Personal Information' }}
                    </div>
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="profile-section">
                    <div class="profile-section-title">
                        <i class="bi bi-shield-lock text-primary"></i>
                        {{ app()->getLocale() == 'bn' ? 'পাসওয়ার্ড পরিবর্তন' : 'Change Password' }}
                    </div>
                    @include('profile.partials.update-password-form')
                </div>

                <div class="profile-section border-danger-subtle">
                    <div class="profile-section-title text-danger">
                        <i class="bi bi-person-x"></i>
                        {{ app()->getLocale() == 'bn' ? 'অ্যাকাউন্ট মুছে ফেলুন' : 'Delete Account' }}
                    </div>
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
