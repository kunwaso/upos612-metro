<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Utils\TwoFactorUtil;
use Illuminate\Http\Request;

class TwoFactorChallengeController extends Controller
{
    protected TwoFactorUtil $twoFactorUtil;

    public function __construct(TwoFactorUtil $twoFactorUtil)
    {
        $this->twoFactorUtil = $twoFactorUtil;
    }

    public function show(Request $request)
    {
        $user = $request->user() ?: auth()->user();
        if (empty($user)) {
            return redirect()->route('login');
        }

        if (! (bool) $user->two_factor_enabled || $request->session()->has('previous_user_id')) {
            return redirect(RouteServiceProvider::HOME);
        }

        if ($this->twoFactorUtil->hasVerifiedTwoFactor($request->session(), (int) $user->id)) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $isLocked = $this->twoFactorUtil->isChallengeLocked((int) $user->id, $request->ip());
        $remainingAttempts = $this->twoFactorUtil->remainingChallengeAttempts((int) $user->id, $request->ip());

        return response()
            ->view('auth.two_factor_challenge', compact('isLocked', 'remainingAttempts'))
            ->setStatusCode($isLocked ? 429 : 200);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $user = $request->user() ?: auth()->user();
        if (empty($user) || ! (bool) $user->two_factor_enabled) {
            return redirect()->route('login');
        }

        if ($request->session()->has('previous_user_id')) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        if ($this->twoFactorUtil->isChallengeLocked((int) $user->id, $request->ip())) {
            return back()->withErrors(['code' => __('two_factor.challenge_locked')]);
        }

        if (! $this->twoFactorUtil->verifyTotpCode((string) $user->two_factor_secret, (string) $request->input('code'))) {
            $attempts = $this->twoFactorUtil->incrementChallengeAttempts((int) $user->id, $request->ip());
            $message = $attempts >= TwoFactorUtil::CHALLENGE_MAX_ATTEMPTS
                ? __('two_factor.challenge_locked')
                : __('two_factor.challenge_invalid');

            return back()->withErrors(['code' => $message]);
        }

        $this->twoFactorUtil->clearChallengeRateLimit((int) $user->id, $request->ip());
        $this->twoFactorUtil->markTwoFactorVerified($request->session(), (int) $user->id);

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    public function verifyRecoveryCode(Request $request)
    {
        $request->validate([
            'recovery_code' => 'required|string|max:50',
        ]);

        $user = $request->user() ?: auth()->user();
        if (empty($user) || ! (bool) $user->two_factor_enabled) {
            return redirect()->route('login');
        }

        if ($request->session()->has('previous_user_id')) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        if ($this->twoFactorUtil->isChallengeLocked((int) $user->id, $request->ip())) {
            return back()->withErrors(['recovery_code' => __('two_factor.challenge_locked')]);
        }

        if (! $this->twoFactorUtil->consumeRecoveryCode($user, (string) $request->input('recovery_code'))) {
            $attempts = $this->twoFactorUtil->incrementChallengeAttempts((int) $user->id, $request->ip());
            $message = $attempts >= TwoFactorUtil::CHALLENGE_MAX_ATTEMPTS
                ? __('two_factor.challenge_locked')
                : __('two_factor.recovery_invalid');

            return back()->withErrors(['recovery_code' => $message]);
        }

        $this->twoFactorUtil->clearChallengeRateLimit((int) $user->id, $request->ip());
        $this->twoFactorUtil->markTwoFactorVerified($request->session(), (int) $user->id);

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
