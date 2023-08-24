<?php

namespace App\Http\Middleware;

use App\Enums\ResponseStatus;
use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        Log::info($request . 'result');
        if ($result !== true) abort(ResponseStatus::UNAUTHORIZED->value, $result);
        return $next($request);
    }
}
