<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocking a customer deletes their sessions, but a "remember me" cookie would
 * quietly sign them back in. Checking on every request closes that gap.
 */
class SignOutBlockedUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isBlocked()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login_id' => __('Your account has been blocked. Please contact us.'),
            ]);
        }

        return $next($request);
    }
}
