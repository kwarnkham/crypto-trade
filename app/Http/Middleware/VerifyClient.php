<?php

namespace App\Http\Middleware;

use App\Enums\ResponseStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyClient
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('Origin') != config('app.frontend_url')) abort(ResponseStatus::BAD_REQUEST->value, 'Not trusted');
        return $next($request);
    }
}
