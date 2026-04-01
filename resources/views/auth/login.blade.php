<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Member Login - {{ config('app.name', 'Organic Store') }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-gray-900 selection:bg-green-500 selection:text-white">
    <!-- The Split Screen Layout -->
    <div class="flex min-h-full">
        <!-- Left Side Image -->
        <div class="relative hidden w-0 flex-1 lg:block relative overflow-hidden bg-green-900">
            <img class="absolute inset-0 h-full w-full object-cover transition-transform duration-[20s] hover:scale-105" src="https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&q=80&w=1920" alt="Fresh organic vegetables">
            <!-- Overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-green-950/90 via-green-900/40 to-transparent mix-blend-multiply"></div>
            <div class="absolute bottom-0 left-0 p-16 text-white z-10 w-full bg-gradient-to-t from-black/60 to-transparent">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-500/20 text-green-300 border border-green-500/30 text-sm font-bold tracking-widest uppercase mb-4 backdrop-blur-md">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z"/></svg>
                    Farm Fresh
                </span>
                <h2 class="text-5xl font-extrabold mb-4 tracking-tight drop-shadow-md">Pure, fresh, organic.</h2>
                <p class="text-xl text-green-50 max-w-xl leading-relaxed opacity-90">Join our community and get access to the freshest groceries delivered straight from the farm to your door.</p>
            </div>
        </div>

        <!-- Right Side Form -->
        <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-28 bg-white z-10 shadow-2xl relative">
            <div class="absolute inset-0 bg-green-50/30"></div>
            <div class="mx-auto w-full max-w-sm lg:w-96 relative z-10">
                <!-- Header -->
                <div>
                   <div class="flex items-center gap-3 mb-10">
                       <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white font-black text-2xl shadow-lg shadow-green-500/30 transform -rotate-6">O</div>
                       <span class="text-2xl font-black text-gray-900 tracking-tight">OrganicStore</span>
                   </div>
                    <h2 class="text-3xl font-extrabold tracking-tight text-gray-900">Welcome back</h2>
                    <p class="mt-2 text-sm text-gray-500 font-medium">
                        Don't have an account? 
                        <a href="{{ route('register') }}" class="font-bold text-green-600 hover:text-green-500 transition-colors">Sign up for free</a>
                    </p>
                </div>

                <!-- Form -->
                <div class="mt-10">
                    <!-- Session Status -->
                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf

                        <!-- Login ID (Mobile or Email) -->
                        <div>
                            <label for="login_id" class="block text-sm font-bold leading-6 text-gray-900">Mobile Number or Email address</label>
                            <div class="mt-2 relative">
                                <input id="login_id" name="login_id" type="text" autocomplete="username" required value="{{ old('login_id') }}" class="block w-full rounded-xl border-0 py-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-green-600 sm:text-sm sm:leading-6 transition-all duration-200 bg-white hover:ring-gray-400" placeholder="017... or example@mail.com">
                                <x-input-error :messages="$errors->get('login_id')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <div class="flex items-center justify-between">
                                <label for="password" class="block text-sm font-bold leading-6 text-gray-900">Password</label>
                                @if (Route::has('password.request'))
                                    <div class="text-sm">
                                        <a href="{{ route('password.request') }}" class="font-bold text-green-600 hover:text-green-500 transition-colors">Forgot password?</a>
                                    </div>
                                @endif
                            </div>
                            <div class="mt-2 relative">
                                <input id="password" name="password" type="password" autocomplete="current-password" required class="block w-full rounded-xl border-0 py-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-green-600 sm:text-sm sm:leading-6 transition-all duration-200 bg-white hover:ring-gray-400">
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center">
                            <input id="remember_me" name="remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-green-600 focus:ring-green-600 transition-colors cursor-pointer">
                            <label for="remember_me" class="ml-3 block text-sm leading-6 text-gray-700 font-medium cursor-pointer">Remember me</label>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" class="flex w-full justify-center items-center rounded-xl bg-gradient-to-r from-green-600 to-emerald-500 px-3 py-3.5 text-sm font-bold text-white shadow-lg shadow-green-600/30 hover:shadow-green-600/40 hover:from-green-500 hover:to-emerald-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 transition-all duration-300 transform hover:-translate-y-1">
                                Sign in to account
                                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
