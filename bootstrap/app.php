<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->alias([
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
        ]);
        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $request) => 
            $request->user()?->isAdmin() ? route('admin.dashboard', absolute: false) : route('home', absolute: false)
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
