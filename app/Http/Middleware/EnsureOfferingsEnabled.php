<?php

namespace App\Http\Middleware;

use App\Domain\Shared\Academy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOfferingsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Academy::enabled('offerings')) {
            abort(404);
        }

        return $next($request);
    }
}
