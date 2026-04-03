<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error | Mango Hut</title>
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
            --dark: #1a1a2e;
            --gray-100: #f1f3f5;
            --gray-300: #dee2e6;
            --accent: #e76f51;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-lg: 20px;
        }

        body {
            font-family: 'Hind Siliguri', sans-serif;
            background-color: #fdfdfd;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }

        .error-container {
            max-width: 600px;
            width: 100%;
        }

        .error-image {
            max-width: 320px;
            margin-bottom: 40px;
            filter: drop-shadow(0 20px 40px rgba(0,0,0,0.08));
        }

        .error-code {
            font-size: 8rem;
            font-weight: 900;
            line-height: 1;
            color: var(--gray-100);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: -1;
            user-select: none;
        }

        h1 {
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 15px;
            font-size: 2.2rem;
        }

        p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 35px;
            line-height: 1.6;
        }

        .btn-premium {
            background: var(--dark);
            color: white;
            border: none;
            padding: 12px 35px;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .btn-premium:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            color: white;
        }
    </style>
</head>
<body>

    <div class="error-container position-relative">
        <div class="error-code">500</div>
        <img src="{{ asset('images/errors/500.png') }}" alt="500 - Server Error" class="error-image">
        
        <h1>বাগানে কিছু একটা সমস্যা হয়েছে!</h1>
        <p>আমরা দুঃখিত, আমাদের সার্ভারে সাময়িক কারিগরি সমস্যা দেখা দিয়েছে। আমরা দ্রত এটি সমাধানের চেষ্টা করছি। কিছুক্ষণ পর আবার চেষ্টা করুন।</p>
        
        <div class="d-flex gap-3 justify-content-center">
            <button onclick="window.location.reload()" class="btn-premium bg-primary shadow-lg border-0 text-white">
                <i class="bi bi-arrow-clockwise"></i> আবার চেষ্টা করুন
            </button>
            <a href="{{ url('/') }}" class="btn-premium">
                <i class="bi bi-house-door"></i> হোম পেজে যান
            </a>
        </div>

        <div class="mt-5 pt-4 text-muted small border-top">
            <p>আমাদের বিশেষজ্ঞ দল এখনই কাজ করছে। <a href="{{ route('contact') }}" class="text-primary text-decoration-none fw-bold">যোগাযোগ করুন</a></p>
        </div>
    </div>

</body>
</html>
