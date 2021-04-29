<?php

namespace Famdirksen\LaravelRequestLogger\Http\Middleware;

use Closure;
use Famdirksen\LaravelRequestLogger\Events\NewRequestEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UriLoggerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('request-logger.enabled', false)) {
            return $next($request);
        }

        try {
            $data = [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'input' => json_encode($request->except([
                    'password',
                    'password_confirmation',
                ])),
                'headers' => $request->headers,
                'logged_at' => now()->toISOString(),
            ];

            if (Auth::check()) {
                $data['user_id'] = Auth::user()->id;
            }

            // Dispatch the event after the response
            NewRequestEvent::dispatchAfterResponse($data);
        } catch (\Exception $exception) {
            report($exception);
        }

        return $next($request);
    }
}
