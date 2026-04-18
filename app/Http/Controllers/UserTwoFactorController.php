<?php

namespace App\Http\Controllers;

use App\User;
use App\Utils\ModuleUtil;
use App\Utils\TwoFactorUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserTwoFactorController extends Controller
{
    protected ModuleUtil $moduleUtil;

    protected TwoFactorUtil $twoFactorUtil;

    public function __construct(ModuleUtil $moduleUtil, TwoFactorUtil $twoFactorUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->twoFactorUtil = $twoFactorUtil;
    }

    public function startSetup(Request $request, $userId)
    {
        $notAllowed = $this->moduleUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        $targetUser = $this->findBusinessUserOrFail($request, (int) $userId);
        $this->authorizeSelf($request, $targetUser);

        if ((bool) $targetUser->two_factor_enabled) {
            return $this->redirectToSettings($targetUser->id, [
                'success' => 0,
                'msg' => __('two_factor.already_enabled'),
            ]);
        }

        $secret = $this->twoFactorUtil->generateSecret();
        $this->twoFactorUtil->storeSetupSecret((int) $targetUser->id, $secret);

        return $this->redirectToSettings($targetUser->id, [
            'success' => 1,
            'msg' => __('two_factor.setup_started'),
        ]);
    }

    public function confirmSetup(Request $request, $userId)
    {
        $notAllowed = $this->moduleUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $targetUser = $this->findBusinessUserOrFail($request, (int) $userId);
        $this->authorizeSelf($request, $targetUser);

        if ((bool) $targetUser->two_factor_enabled) {
            return $this->redirectToSettings($targetUser->id, [
                'success' => 0,
                'msg' => __('two_factor.already_enabled'),
            ]);
        }

        $setupPayload = $this->twoFactorUtil->getSetupPayload((int) $targetUser->id);
        if (empty($setupPayload['secret'])) {
            return $this->redirectToSettings($targetUser->id, [
                'success' => 0,
                'msg' => __('two_factor.setup_expired'),
            ]);
        }

        if (! $this->twoFactorUtil->verifyTotpCode((string) $setupPayload['secret'], (string) $request->input('code'))) {
            return $this->redirectToSettings($targetUser->id, [
                'success' => 0,
                'msg' => __('two_factor.setup_invalid_code'),
            ]);
        }

        $recoveryCodes = $this->twoFactorUtil->generateRecoveryCodes(TwoFactorUtil::RECOVERY_CODES_COUNT);
        $hashedRecoveryCodes = $this->twoFactorUtil->hashRecoveryCodes($recoveryCodes);

        DB::transaction(function () use ($targetUser, $setupPayload, $hashedRecoveryCodes) {
            $targetUser->two_factor_enabled = true;
            $targetUser->two_factor_secret = (string) $setupPayload['secret'];
            $targetUser->two_factor_recovery_codes = $this->twoFactorUtil->encodeRecoveryCodes($hashedRecoveryCodes);
            $targetUser->two_factor_confirmed_at = now();
            $targetUser->save();
        });

        $downloadToken = Str::random(64);
        $this->twoFactorUtil->storeRecoveryDownloadPayload((int) $targetUser->id, $downloadToken, $recoveryCodes);
        $this->twoFactorUtil->clearSetup((int) $targetUser->id);
        $this->twoFactorUtil->clearChallengeRateLimitForUser((int) $targetUser->id);
        $this->twoFactorUtil->clearVerifiedTwoFactor($request->session());

        $request->session()->flash('two_factor.recovery_codes', $recoveryCodes);
        $request->session()->flash('two_factor.recovery_download_token', $downloadToken);

        $this->moduleUtil->activityLog(
            $targetUser,
            'two_factor_enabled',
            null,
            ['name' => $targetUser->user_full_name, 'id' => $targetUser->id],
            true,
            $targetUser->business_id
        );

        return $this->redirectToSettings($targetUser->id, [
            'success' => 1,
            'msg' => __('two_factor.setup_success'),
        ]);
    }

    public function disableSelf(Request $request, $userId)
    {
        $notAllowed = $this->moduleUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        $request->validate([
            'current_password' => 'required|string',
        ]);

        $targetUser = $this->findBusinessUserOrFail($request, (int) $userId);
        $this->authorizeSelf($request, $targetUser);

        if (! (bool) $targetUser->two_factor_enabled) {
            return $this->redirectToSettings($targetUser->id, [
                'success' => 0,
                'msg' => __('two_factor.not_enabled'),
            ]);
        }

        if (! Hash::check((string) $request->input('current_password'), (string) $targetUser->password)) {
            return redirect()
                ->route('users.show', ['user' => $targetUser->id, 'tab' => 'settings'])
                ->withErrors(['current_password' => __('two_factor.password_invalid')]);
        }

        $this->clearTwoFactorState($targetUser);
        $this->twoFactorUtil->clearVerifiedTwoFactor($request->session());

        $this->moduleUtil->activityLog(
            $targetUser,
            'two_factor_disabled',
            null,
            ['name' => $targetUser->user_full_name, 'id' => $targetUser->id],
            true,
            $targetUser->business_id
        );

        return $this->redirectToSettings($targetUser->id, [
            'success' => 1,
            'msg' => __('two_factor.disable_success'),
        ]);
    }

    public function resetForUser(Request $request, $userId)
    {
        $notAllowed = $this->moduleUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        $actingUser = $request->user() ?: auth()->user();
        if (empty($actingUser) || ! $actingUser->can('superadmin')) {
            abort(403, __('two_factor.reset_denied'));
        }

        $targetUser = $this->findBusinessUserOrFail($request, (int) $userId);
        $this->clearTwoFactorState($targetUser);

        if ((int) $actingUser->id === (int) $targetUser->id) {
            $this->twoFactorUtil->clearVerifiedTwoFactor($request->session());
        }

        $this->moduleUtil->activityLog(
            $targetUser,
            'two_factor_reset',
            null,
            ['name' => $targetUser->user_full_name, 'id' => $targetUser->id],
            true,
            $targetUser->business_id
        );

        return $this->redirectToSettings($targetUser->id, [
            'success' => 1,
            'msg' => __('two_factor.reset_success'),
        ]);
    }

    public function downloadRecoveryCodes(Request $request, $userId, string $token)
    {
        $targetUser = $this->findBusinessUserOrFail($request, (int) $userId);
        $this->authorizeSelf($request, $targetUser);

        $codes = $this->twoFactorUtil->consumeRecoveryDownloadPayload((int) $targetUser->id, $token);
        if (empty($codes)) {
            return $this->redirectToSettings($targetUser->id, [
                'success' => 0,
                'msg' => __('two_factor.recovery_download_failed'),
            ]);
        }

        $content = implode(PHP_EOL, array_merge([
            config('app.name', 'UPOS').' - '.__('two_factor.recovery_codes_heading'),
            __('two_factor.recovery_codes_description'),
            'User: '.$targetUser->user_full_name,
            'Generated: '.now()->toDateTimeString(),
            str_repeat('-', 32),
        ], $codes));

        $filename = 'two-factor-recovery-codes-'.$targetUser->id.'-'.now()->format('YmdHis').'.txt';

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function authorizeSelf(Request $request, User $targetUser): void
    {
        $actingUser = $request->user() ?: auth()->user();
        if (empty($actingUser) || (int) $actingUser->id !== (int) $targetUser->id) {
            abort(403, __('two_factor.settings_access_denied'));
        }
    }

    protected function clearTwoFactorState(User $targetUser): void
    {
        $targetUser->two_factor_enabled = false;
        $targetUser->two_factor_secret = null;
        $targetUser->two_factor_recovery_codes = null;
        $targetUser->two_factor_confirmed_at = null;
        $targetUser->save();

        $this->twoFactorUtil->clearSetup((int) $targetUser->id);
        $this->twoFactorUtil->clearChallengeRateLimitForUser((int) $targetUser->id);
    }

    protected function findBusinessUserOrFail(Request $request, int $userId): User
    {
        $businessId = (int) $request->session()->get('user.business_id');

        return User::where('business_id', $businessId)->findOrFail($userId);
    }

    /**
     * @param  array<string, mixed>  $status
     */
    protected function redirectToSettings(int $userId, array $status)
    {
        return redirect()
            ->route('users.show', ['user' => $userId, 'tab' => 'settings'])
            ->with('status', $status);
    }
}
