<?php

namespace Famdirksen\LaravelRequestLogger\Http\Middleware;

use Closure;
use Famdirksen\LaravelRequestLogger\Contracts\ReferralCookieDurationContract;
use Famdirksen\LaravelRequestLogger\Events\ReferralLinkVisitEvent;
use Famdirksen\LaravelRequestLogger\Models\ReferralAccount;
use Illuminate\Http\Request;

class CheckReferralMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $referralCookieName = config('referral.cookie_name');

        if ($request->hasCookie($referralCookieName) && ! config('referral.overwrite_previous_referral', false)) {
            // Return next action when there is a referral cookie set
            return $next($request);
        }

        if (($ref = $request->query('r')) && $referralAccount = ReferralAccount::byReferralToken($ref)) {
            if (! $request->hasCookie($referralCookieName) || $request->cookie($referralCookieName) !== $ref) {
                // Register cookie as link-visited
                event(new ReferralLinkVisitEvent($referralAccount));
            }

            $cookieDuration = config('referral.cookie_duration');

            if (! $cookieDuration instanceof ReferralCookieDurationContract) {
                throw new \Exception('Invalid `cookie_duration` class defined in configuration.');
            }

            return redirect($request->fullUrl())
                ->withCookie(cookie()->make($referralCookieName, $ref, $cookieDuration::getMinutesToStore()));
        }

        return $next($request);
    }
}
