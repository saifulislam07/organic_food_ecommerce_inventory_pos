<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Authentication') – {{ \App\Models\Setting::get('site_title', 'Mango Hut') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #2d6a4f;
            --primary-light: #40916c;
            --primary-dark: #1b4332;
            --secondary: #f4a261;
            --accent: #e76f51;
            --light: #f8f9fa;
            --dark: #1a1a2e;
            --gray-100: #f1f3f5;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --white: #ffffff;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 20px;
        }

        body {
            font-family: 'Hind Siliguri', sans-serif;
            background-color: var(--white);
            height: 100vh;
            overflow: hidden;
            display: flex;
        }

        /* Split Screen Optimization */
        .auth-container {
            display: flex;
            width: 100%;
            height: 100vh;
        }

        .auth-image-side {
            flex: 1.2;
            position: relative;
            background: var(--primary-dark);
            overflow: hidden;
        }

        .auth-image-side img {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover;
            opacity: 0.8;
            filter: brightness(0.8);
        }

        .auth-overlay-content {
            position: absolute;
            bottom: 60px;
            left: 60px;
            right: 60px;
            color: white;
            z-index: 10;
        }

        .auth-form-side {
            flex: 1;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            position: relative;
            box-shadow: -10px 0 30px rgba(0,0,0,0.05);
            overflow-y: auto;
        }

        .auth-brand {
            position: absolute;
            top: 40px;
            left: 60px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 20;
        }

        .brand-icon { font-size: 2rem; }
        .brand-text { font-size: 1.5rem; font-weight: 800; color: var(--dark); }
        .brand-highlight { color: var(--primary); }

        .auth-form-wrapper {
            max-width: 420px;
            margin: 0 auto;
            width: 100%;
        }

        /* Form Styling */
        .form-label {
            font-weight: 700;
            color: var(--dark);
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: var(--radius-md);
            padding: 12px 18px;
            border: 1px solid var(--gray-300);
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(45, 106, 79, 0.1);
        }

        .btn-primary-custom {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-weight: 700;
            width: 100%;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(45, 106, 79, 0.2);
        }

        .btn-primary-custom:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(45, 106, 79, 0.3);
            color: white;
        }

        .link-custom {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition);
        }

        .link-custom:hover {
            color: var(--primary-light);
        }

        /* Mobile Adjustments */
        @media (max-width: 992px) {
            .auth-image-side { display: none; }
            body { overflow: auto; }
            .auth-form-side { 
                flex: 1; 
                padding: 40px 20px; 
                box-shadow: none;
                height: 100vh;
            }
            .auth-brand { left: 20px; top: 30px; }
            .auth-form-wrapper { padding-top: 60px; }
        }

        /* Decorative Elements */
        .leaf-decoration {
            position: absolute;
            width: 150px;
            opacity: 0.05;
            pointer-events: none;
            z-index: 0;
        }
    </style>
    @stack('styles')
</head>
<body>

    <div class="auth-container">
        <!-- Left Side: Visual -->
        <div class="auth-image-side">
            <img src="{{ asset('images/auth-bg.png') }}" alt="Mango Orchard">
            <div class="auth-overlay-content">
                <span class="badge bg-secondary mb-3 px-3 py-2 text-uppercase tracking-wider fw-bold">Premium Quality</span>
                <h1 class="display-4 fw-black text-white mb-3">খাঁটি ও তাজা আমের জাদুকরী স্বাদ।</h1>
                <p class="lead text-white-50">চাঁপাই নবাবগঞ্জের বিখ্যাত বাগান থেকে সরাসরি সংগ্রহ করা শতভাগ অর্গানিক পণ্য এখন আপনার দোরগোড়ায়।</p>
            </div>
        </div>

        <!-- Right Side: Form -->
        <div class="auth-form-side">
            <a href="{{ route('home') }}" class="auth-brand">
                <span class="brand-icon">🥭</span>
                <span class="brand-text">Mango<span class="brand-highlight">Hut</span></span>
            </a>

            <div class="auth-form-wrapper">
                @yield('content')
            </div>

            <!-- Footer Small -->
            <div class="mt-5 text-center text-muted small">
                <p>&copy; {{ date('Y') }} Mango Hut. All rights reserved.</p>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    @stack('scripts')
</body>
</html>
