<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hide Fortify's register HTTP surface while public signup is off.
 */
class EnsureRegistrationIsEnabled
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('register', 'register.store')) {
            return $next($request);
        }

        if (! config('lms.registration_enabled')) {
            abort(404);
        }

        return $next($request);
    }
}
