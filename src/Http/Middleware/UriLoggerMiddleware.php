<?php

namespace Famdirksen\LaravelRequestLogger\Http\Middleware;

use Auth;
use Closure;
use Famdirksen\LaravelRequestLogger\Events\NewRequestEvent;
use Illuminate\Http\Request;
use Route;

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
                $user = Auth::user();

                $data['user_id'] = $user->getAuthIdentifier();
                $data['user_type'] = get_class($user);
                
                $data['api_token_id'] = $this->getApiTokenId($user);
            }

            $data['route']['name'] = Route::currentRouteName();
            $data['finished_at'] = now();
            $data['status_code'] = $response->getStatusCode();
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

    /**
     * Attempt to get the ID of the current API token.
     * Supports Sanctum and Passport.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|mixed $user
     * @return string|int|null
     */
    private function getApiTokenId($user)
    {
        // 1. Check config
        if (! config('request-logger.store_api_token_id', false)) {
            return null;
        }

        if (! $user) {
            return null;
        }

        // 2. Check optional user method override
        if (method_exists($user, 'shouldStoreUsedApiToken')) {
            if (! $user->shouldStoreUsedApiToken()) {
                return null;
            }
        }

        // 3. Sanctum check
        if (method_exists($user, 'currentAccessToken')) {
            $token = $user->currentAccessToken();

            if ($token) {
                return $token->id;
            }
        }

        // 4. Passport check
        if (method_exists($user, 'token')) {
            $token = $user->token();

            if ($token) {
                return $token->id;
            }
        }

        return null;
    }
}
