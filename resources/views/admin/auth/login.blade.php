<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Portal - {{ config('app.name', 'System') }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-gray-200 selection:bg-indigo-500 selection:text-white">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-slate-950 to-black">
        
        <!-- Abstract Background Glows -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-600/10 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-[400px] h-[400px] bg-sky-500/10 rounded-full blur-[100px] pointer-events-none"></div>
        
        <!-- Login Card -->
        <div class="w-full max-w-md space-y-8 relative z-10 bg-slate-900/60 backdrop-blur-2xl p-10 sm:p-12 rounded-[2rem] border border-white/5 shadow-2xl ring-1 ring-white/10">
            
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-gradient-to-br from-indigo-500 to-sky-400 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/25 mb-6 group cursor-default ring-1 ring-white/20">
                    <svg class="w-8 h-8 text-white group-hover:scale-110 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"></path>
                    </svg>
                </div>
                <h2 class="mt-2 text-3xl font-black tracking-tight text-white drop-shadow-sm">System Admin</h2>
                <p class="mt-2 text-sm text-slate-400 font-medium">Secure Backend Authentication</p>
            </div>

            <!-- Form -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('admin.login') }}" class="mt-10 space-y-6">
                @csrf

                <div class="space-y-5">
                    <!-- Login ID -->
                    <div>
                         <label for="login_id" class="sr-only">Email address or Mobile</label>
                         <div class="relative group/email">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-500 group-focus-within/email:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input id="login_id" name="login_id" type="text" autocomplete="username" required value="{{ old('login_id') }}" class="block w-full pl-11 pr-4 py-3.5 border border-white/10 rounded-xl leading-5 bg-slate-950/50 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-slate-900 sm:text-sm transition-all duration-200 shadow-inner" placeholder="Admin Email or Mobile">
                         </div>
                         <x-input-error :messages="$errors->get('login_id')" class="mt-2 text-rose-400" />
                    </div>

                    <!-- Password -->
                    <div>
                         <label for="password" class="sr-only">Password</label>
                         <div class="relative group/password">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-500 group-focus-within/password:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input id="password" name="password" type="password" autocomplete="current-password" required class="block w-full pl-11 pr-4 py-3.5 border border-white/10 rounded-xl leading-5 bg-slate-950/50 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-slate-900 sm:text-sm transition-all duration-200 shadow-inner" placeholder="Password">
                         </div>
                         <x-input-error :messages="$errors->get('password')" class="mt-2 text-rose-400" />
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div class="flex items-center">
                        <input id="remember_me" name="remember" type="checkbox" class="h-4 w-4 bg-slate-900 border-white/20 rounded cursor-pointer text-indigo-500 focus:ring-indigo-500 focus:ring-offset-slate-900 transition-colors">
                        <label for="remember_me" class="ml-3 block text-sm text-slate-400 font-medium cursor-pointer">Remember me</label>
                    </div>

                    @if (Route::has('password.request'))
                        <div class="text-sm">
                            <a href="{{ route('password.request') }}" class="font-bold text-indigo-400 hover:text-indigo-300 transition-colors">Forgot password?</a>
                        </div>
                    @endif
                </div>

                <div class="pt-4">
                    <button type="submit" class="group relative w-full flex justify-center py-3.5 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-indigo-500 shadow-[0_0_20px_rgba(79,70,229,0.4)] transition-all duration-300 transform hover:-translate-y-1">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-4">
                           <svg class="h-5 w-5 text-indigo-400 group-hover:text-indigo-300 transition-colors" fill="currentColor" viewBox="0 0 20 20">
                             <path fill-rule="evenodd" d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H9V7a1 1 0 012 0v2h2V7a3 3 0 00-3-3z" clip-rule="evenodd" />
                           </svg>
                        </span>
                        Authorize Access
                    </button>
                    
                    <div class="mt-8 flex items-center justify-center space-x-2 text-slate-500">
                        <div class="h-px bg-white/10 w-full"></div>
                        <p class="text-[0.65rem] uppercase tracking-[0.2em] font-black shrink-0 px-2">Authorized Personnel Only</p>
                        <div class="h-px bg-white/10 w-full"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
