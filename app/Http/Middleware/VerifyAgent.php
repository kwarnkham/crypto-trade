<?php

namespace App\Http\Middleware;

use App\Enums\ResponseStatus;
use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAgent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $result = Agent::verify($request);
        if ($result !== true) {
            abort(ResponseStatus::UNAUTHENTICATED->value, $result);
        }

        return $next($request);
    }
}
