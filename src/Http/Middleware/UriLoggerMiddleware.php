<?php

namespace Famdirksen\LaravelRequestLogger\Http\Middleware;

use Auth;
use Closure;
use Famdirksen\LaravelRequestLogger\Events\NewRequestEvent;
use Illuminate\Http\Request;
use Route;

class UriLoggerMiddleware
{
    /**
     * Replacement written instead of a sensitive header value.
     */
    private const REDACTED = '[redacted]';

    /**
     * Fallback for `request-logger.redact-headers`.
     *
     * The published config file normally supplies this list, but keeping a copy
     * here means an install that sets the key to null still gets the secure
     * behaviour rather than logging credentials in cleartext.
     *
     * @var string[]
     */
    private const DEFAULT_REDACT_HEADERS = [
        'authorization',
        'proxy-authorization',
        'cookie',
        'set-cookie',
        'x-api-key',
        'x-auth-token',
        'x-xsrf-token',
        'php-auth-pw',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (! config('request-logger.enabled', false)) {
            return $next($request);
        }

        // Init the data
        try {
            $data = [
                'ip' => $request->ip(),
                'url' => $this->redactedUrl($request),
                'method' => $request->method(),
                'input' => json_encode($request->except(
                    config('request-logger.except-input', [])
                )),
                'headers' => $this->redactedHeaders($request),
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
     * The request URL with sensitive query-string values replaced.
     *
     * `except-input` strips these keys from the logged input, but the full URL is
     * stored separately and kept its query string verbatim — so a secret passed
     * as a query parameter was still written to request_logs.url in cleartext.
     *
     * The URL is only rebuilt when one of the keys is actually present, so
     * ordinary URLs are logged byte-for-byte as before. Only top-level query
     * keys are matched; nested keys such as filter[password] are not.
     *
     * @return string
     */
    private function redactedUrl(Request $request)
    {
        $redact = config('request-logger.except-input', []);

        if (! is_array($redact) || $redact === []) {
            return $request->fullUrl();
        }

        $query = $request->query();

        if (! is_array($query)) {
            return $request->fullUrl();
        }

        $present = array_intersect(array_keys($query), $redact);

        if ($present === []) {
            return $request->fullUrl();
        }

        foreach ($present as $key) {
            $query[$key] = self::REDACTED;
        }

        return $request->url().'?'.http_build_query($query);
    }

    /**
     * A copy of the request's headers with sensitive values replaced.
     *
     * The bag is cloned so redaction never mutates the live request. Values are
     * replaced rather than removed, so the log still shows that (for example) an
     * Authorization header was sent — only its secret is dropped.
     *
     * @return \Symfony\Component\HttpFoundation\HeaderBag
     */
    private function redactedHeaders(Request $request)
    {
        $headers = clone $request->headers;

        $redact = config('request-logger.redact-headers', self::DEFAULT_REDACT_HEADERS);

        if (! is_array($redact)) {
            $redact = self::DEFAULT_REDACT_HEADERS;
        }

        foreach ($redact as $header) {
            if ($headers->has($header)) {
                $headers->set($header, self::REDACTED);
            }
        }

        return $headers;
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

            if ($token && $this->isPassportToken($user, $token)) {
                return null;
            }

            if ($token) {
                return $token->id;
            }
        }

        return null;
    }

    /**
     * Token types owned by Laravel Passport.
     *
     * ScopeAuthorizable covers AccessToken and TransientToken on Passport 13+;
     * Token is the Eloquent model that older token guards hand over and does not
     * implement that contract. Entries for classes that are not installed are
     * harmless -- `instanceof` against an unknown class is false and triggers no
     * autoloading.
     *
     * @var string[]
     */
    private const PASSPORT_TOKEN_TYPES = [
        'Laravel\\Passport\\Contracts\\ScopeAuthorizable',
        'Laravel\\Passport\\AccessToken',
        'Laravel\\Passport\\Token',
        'Laravel\\Passport\\TransientToken',
    ];

    /**
     * Whether the token returned by currentAccessToken() is in fact the Passport
     * OAuth token, which belongs in passport_token_id instead.
     *
     * An application may expose a single currentAccessToken() that returns either
     * a Sanctum personal access token or a Passport OAuth token, so both guards
     * can be used side by side — Sanctum's and Passport's HasApiTokens traits
     * collide on this method name, so they cannot both be applied to one model.
     * Without this check the Passport token is reported as a Sanctum PAT and the
     * same id is written to api_token_id and passport_token_id.
     *
     * Detected two ways, because neither alone is sufficient. The class check
     * covers Passport's own token types across major versions -- the list is
     * deliberately broad since `instanceof` against a class that is not
     * installed simply yields false, and a Sanctum token can never match a
     * Laravel\Passport type. The identity check then covers applications that
     * hand back a wrapper or proxy of their own class: if the model's Passport
     * accessor returns this very object, it is the Passport token whatever its
     * class.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @param  mixed  $token
     * @return bool
     */
    private function isPassportToken($user, $token)
    {
        foreach (self::PASSPORT_TOKEN_TYPES as $type) {
            if ($token instanceof $type) {
                return true;
            }
        }

        return method_exists($user, 'token') && $user->token() === $token;
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
