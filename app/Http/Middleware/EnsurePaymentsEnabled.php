<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hide payment HTTP surface while product payments are disabled.
 */
class EnsurePaymentsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('lms.mode') !== 'commercial' || ! config('lms.payment.enabled')) {
            abort(404);
        }

        return $next($request);
    }
}
