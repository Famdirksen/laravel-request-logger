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
                $data['passport_token_id'] = $this->getPassportTokenId($user);
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
        if (! config('request-logger.store_api_token_id', false)) {
            return null;
        }

        if (! $user) {
            return null;
        }

        if (method_exists($user, 'shouldStoreUsedApiToken')) {
            if (! $user->shouldStoreUsedApiToken()) {
                return null;
            }
        }

        // Sanctum check
        if (method_exists($user, 'currentAccessToken')) {
            $token = $user->currentAccessToken();

            if ($token) {
                return $token->id;
            }
        }

        return null;
    }

    /**
     * Attempt to get the ID of the current Passport OAuth token, kept in its own
     * column (see getApiTokenId()) so it can't collide with a Sanctum PAT id.
     *
     * Opt-in: returns null unless `request-logger.store_passport_token_id` is
     * true. Existing consumers that never touch this config key see no change.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @return string|null
     */
    private function getPassportTokenId($user)
    {
        if (! config('request-logger.store_passport_token_id', false)) {
            return null;
        }

        if (! $user) {
            return null;
        }

        if (method_exists($user, 'shouldStoreUsedApiToken') && ! $user->shouldStoreUsedApiToken()) {
            return null;
        }

        if (method_exists($user, 'token')) {
            $token = $user->token();

            if ($token) {
                return $token->id;
            }
        }

        return null;
    }
}
