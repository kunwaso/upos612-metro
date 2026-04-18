<?php

namespace App\Http\Middleware;

use App\Utils\TwoFactorUtil;
use Closure;

class EnsureTwoFactorVerified
{
    protected TwoFactorUtil $twoFactorUtil;

    public function __construct(TwoFactorUtil $twoFactorUtil)
    {
        $this->twoFactorUtil = $twoFactorUtil;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $request->user()) {
            return $next($request);
        }

        $user = $request->user();

        if (! (bool) $user->two_factor_enabled) {
            return $next($request);
        }

        if ($request->session()->has('previous_user_id')) {
            return $next($request);
        }

        if ($this->twoFactorUtil->hasVerifiedTwoFactor($request->session(), (int) $user->id)) {
            return $next($request);
        }

        return redirect()->route('two-factor.challenge.show');
    }
}
