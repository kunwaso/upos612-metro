<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\VasAccounting\Entities\VasAccount;
use Modules\VasAccounting\Http\Requests\StoreVasAccountRequest;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class ChartOfAccountsController extends VasBaseController
{
    public function __construct(protected VasAccountingUtil $vasUtil)
    {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.chart.manage');

        $businessId = $this->businessId($request);
        $bootstrap = $this->vasUtil->ensureBusinessBootstrapped($businessId, (int) auth()->id());
        $accounts = VasAccount::query()
            ->where('business_id', $businessId)
            ->orderBy('account_code')
            ->get();
        $parentOptions = $this->vasUtil->chartOptions($businessId);
        $bootstrapStatus = $this->vasUtil->bootstrapStatus($businessId);

        return view('vasaccounting::chart.index', [
            'accounts' => $accounts,
            'parentOptions' => $parentOptions,
            'bootstrapStatus' => $bootstrapStatus,
            'autoBootstrapped' => $bootstrap['bootstrapped'],
        ]);
    }

    public function store(StoreVasAccountRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $parentId = (int) ($request->validated()['parent_id'] ?? 0);
        $parent = null;
        if ($parentId > 0) {
            $parent = VasAccount::query()
                ->where('business_id', $businessId)
                ->whereKey($parentId)
                ->first();
        }

        VasAccount::updateOrCreate(
            [
                'business_id' => $businessId,
                'account_code' => $request->validated()['account_code'],
            ],
            [
                'account_name' => $request->validated()['account_name'],
                'account_type' => $request->validated()['account_type'],
                'account_category' => $request->validated()['account_category'] ?? null,
                'normal_balance' => $request->validated()['normal_balance'],
                'parent_id' => $parent?->id,
                'level' => $parent ? ((int) $parent->level + 1) : 1,
                'created_by' => auth()->id(),
                'is_active' => true,
                'allows_manual_entries' => true,
                'is_control_account' => false,
                'is_system' => false,
            ]
        );

        if ($parent) {
            $parent->forceFill([
                'is_control_account' => true,
                'allows_manual_entries' => false,
            ])->save();
        }

        return redirect()
            ->route('vasaccounting.chart.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.account_saved')]);
    }
}
