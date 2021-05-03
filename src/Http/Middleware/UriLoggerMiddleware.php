<?php

namespace Famdirksen\LaravelRequestLogger\Http\Middleware;

use Auth;
use Closure;
use Famdirksen\LaravelRequestLogger\Events\NewRequestEvent;
use Illuminate\Http\Request;

class UriLoggerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('request-logger.enabled', false)) {
            return $next($request);
        }

        // Init the data
        try {
            $data = [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'input' => json_encode($request->except(
                    config('request-logger.except-input', [])
                )),
                'headers' => $request->headers,
                'logged_at' => now(),
            ];
        } catch (\Exception $exception) {
            report($exception);
        }

        // Handle the request
        $response = $next($request);

        // Set the user
        try {
            if (Auth::check()) {
                $data['user_id'] = Auth::user()->getAuthIdentifier();
            }

            $data['finished_at'] = now();
        } catch (\Exception $exception) {
            report($exception);
        }

        // Handle the event
        try {
            event(new NewRequestEvent($data));
        } catch (\Exception $exception) {
            report($exception);
        }

        return $response;
    }
}
