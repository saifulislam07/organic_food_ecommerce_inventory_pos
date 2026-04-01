<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name', 'Laravel') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Style -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .animate-blob {
            animation: blob 7s infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
    </style>
</head>
<body class="antialiased bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 font-sans selection:bg-green-500 selection:text-white">
    <div class="min-h-screen flex items-center justify-center relative overflow-hidden">
        <!-- Background Decoration -->
        <div class="absolute inset-0 bg-gradient-to-br from-green-50 to-green-100 dark:from-gray-800 dark:to-gray-900 z-0"></div>
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-green-400 rounded-full mix-blend-multiply filter blur-3xl opacity-30 dark:opacity-10 animate-blob"></div>
        <div class="absolute top-1/3 right-1/4 w-96 h-96 bg-emerald-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 dark:opacity-10 animate-blob animation-delay-2000"></div>

        <!-- Main Content Glassmorphism Card -->
        <div class="relative z-10 w-full max-w-3xl px-6 py-16 mx-4 text-center 
                    bg-white/60 dark:bg-gray-800/60 backdrop-blur-xl 
                    rounded-3xl shadow-2xl border border-white/40 dark:border-gray-700/50">
            
            <div class="mb-4">
                <span class="text-9xl md:text-[12rem] font-black text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-emerald-400 drop-shadow-sm">
                    @yield('code')
                </span>
            </div>

            <h1 class="text-4xl md:text-5xl font-extrabold mb-6 tracking-tight text-gray-900 dark:text-white">@yield('message')</h1>
            
            <p class="text-lg md:text-xl text-gray-600 dark:text-gray-400 mb-12 max-w-lg mx-auto leading-relaxed">
                @yield('description', "We couldn't process your request right now. Please navigate back or try again later.")
            </p>

            <a href="{{ url('/') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white transition-all duration-300 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full hover:from-green-600 hover:to-emerald-700 shadow-xl shadow-green-500/30 hover:shadow-green-500/50 transform hover:-translate-y-1 focus:outline-none focus:ring-4 focus:ring-green-500/50 active:translate-y-0">
                <svg class="w-6 h-6 mr-3 -ml-1 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Return to Homepage
            </a>
        </div>
    </div>
</body>
</html>
