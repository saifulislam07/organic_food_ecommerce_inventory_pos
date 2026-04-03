<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin Gateway – {{ config('app.name', 'Mango Hut') }}</title>

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
            --dark: #081c15;
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
            --radius-md: 12px;
            --radius-lg: 20px;
        }

        body {
            font-family: 'Hind Siliguri', sans-serif;
            background-color: var(--dark);
            height: 100vh;
            overflow: hidden;
        }

        .auth-container {
            display: flex;
            width: 100%;
            height: 100vh;
        }

        .auth-image-side {
            flex: 1.5;
            position: relative;
            overflow: hidden;
            background: var(--dark);
        }

        .auth-image-side img {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover;
            opacity: 0.5;
            filter: grayscale(0.2) contrast(1.1);
        }

        .auth-overlay-content {
            position: absolute;
            bottom: 80px;
            left: 80px;
            right: 80px;
            color: white;
            z-index: 10;
        }

        .auth-form-side {
            flex: 1;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 80px;
            position: relative;
            box-shadow: -20px 0 50px rgba(0,0,0,0.3);
            border-left: 1px solid rgba(255,255,255,0.05);
        }

        .auth-brand {
            position: absolute;
            top: 40px;
            left: 80px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon { font-size: 2.2rem; }
        .brand-text { font-size: 1.8rem; font-weight: 800; color: var(--dark); letter-spacing: -1px; }
        .brand-highlight { color: var(--primary); }

        .auth-form-wrapper {
            max-width: 400px;
            margin: 0 auto;
            width: 100%;
        }

        .admin-badge {
            background: var(--dark);
            color: white;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 800;
            margin-bottom: 25px;
            display: inline-block;
        }

        .form-label {
            font-weight: 700;
            color: var(--dark);
            font-size: 0.85rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-control {
            border-radius: var(--radius-md);
            padding: 14px 20px;
            border: 2px solid var(--gray-200);
            transition: var(--transition);
            font-size: 1rem;
            background: #fcfcfc;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 5px rgba(45, 106, 79, 0.08);
            background: white;
        }

        .btn-admin {
            background: var(--dark);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: var(--radius-md);
            font-weight: 800;
            width: 100%;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(8, 28, 21, 0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-admin:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(8, 28, 21, 0.3);
            color: white;
        }

        .footer-secure {
            position: absolute;
            bottom: 40px;
            left: 0;
            width: 100%;
            text-align: center;
            color: var(--gray-400);
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        @media (max-width: 1200px) {
            .auth-image-side { display: none; }
            .auth-form-side { padding: 40px; }
            .auth-brand { left: 40px; }
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <!-- Visual Layered Info -->
        <div class="auth-image-side">
            <img src="{{ asset('images/admin-auth-bg.png') }}" alt="Mango Hut Admin">
            <div class="auth-overlay-content">
                <h1 class="display-3 fw-black text-white pe-5 mb-4">স্বাগতম এডমিন পোর্টাল</h1>
                <p class="lead text-white-50 opacity-75">নিরাপদ এবং দক্ষভাবে আপনার ব্যবসা পরিচালনা করুন। ম্যাংগো হাট ড্যাশবোর্ডে লগইন করুন।</p>
                
                <div class="d-flex gap-4 mt-5">
                    <div class="text-white">
                        <div class="fs-4 fw-bold">100%</div>
                        <div class="small text-white-50">নিরাপদ</div>
                    </div>
                    <div class="vr bg-white opacity-25"></div>
                    <div class="text-white">
                        <div class="fs-4 fw-bold">Live</div>
                        <div class="small text-white-50">ইনভেন্টরি</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secure Form Side -->
        <div class="auth-form-side">
            <a href="{{ route('home') }}" class="auth-brand">
                <span class="brand-icon">🥭</span>
                <span class="brand-text">Mango<span class="brand-highlight">Hut</span></span>
            </a>

            <div class="auth-form-wrapper">
                <div class="admin-badge">Admin Gateway</div>
                <h2 class="fw-black text-dark mb-4">প্যানেলে লগইন করুন</h2>

                @if (session('status'))
                    <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="login_id" class="form-label">
                            <i class="bi bi-shield-lock"></i> এডমিন ইউজারনেম/মোবাইল
                        </label>
                        <input id="login_id" name="login_id" type="text" 
                               class="form-control @error('login_id') is-invalid @enderror" 
                               value="{{ old('login_id') }}" required autofocus placeholder="Admin identity">
                        @error('login_id')
                            <div class="invalid-feedback fw-bold">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label for="password" class="form-label mb-0">
                                <i class="bi bi-key"></i> পাসওয়ার্ড
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-muted small text-decoration-none hover:text-dark">রিসেট করুন?</a>
                            @endif
                        </div>
                        <input id="password" name="password" type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               required autocomplete="current-password" placeholder="Access key">
                        @error('password')
                            <div class="invalid-feedback fw-bold">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                            <label class="form-check-label text-muted small fw-bold" for="remember_me">
                                ব্রাউজারে মনে রাখুন
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-admin">
                        সুরক্ষিত প্রবেশ <i class="bi bi-arrow-right-short fs-4"></i>
                    </button>
                </form>
            </div>

            <div class="footer-secure">
                <i class="bi bi-shield-fill-check text-primary"></i> 256-bit SSL Secure Authentication
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
