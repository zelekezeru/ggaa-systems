<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Force users still on their admin-issued default password to set a new one
     * before they can reach any other part of the application. Only the
     * change-password screen and logout are allowed through.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password
            && ! $request->routeIs('password.change', 'password.change.update', 'logout')) {
            // Don't trap non-navigational requests (asset/JSON polling) in a redirect.
            if ($request->isMethod('GET') && ! $request->expectsJson()) {
                return redirect()->route('password.change');
            }

            return redirect()->route('password.change')
                ->with('status', 'Please set a new password to continue.');
        }

        return $next($request);
    }
}
