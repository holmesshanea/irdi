<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveMember
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            ! $request->user()
            || $request->user()->membership_status !== 'active'
        ) {
            abort(403);
        }

        return $next($request);
    }
}
