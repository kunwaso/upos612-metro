<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\Request;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class EnterpriseCenterController extends VasBaseController
{
    public function __construct(protected VasAccountingUtil $vasUtil)
    {
    }

    public function show(Request $request)
    {
        $domain = (string) $request->route('domain');
        $domainConfig = $this->vasUtil->enterpriseDomainConfig($domain);
        $this->authorizePermission((string) $domainConfig['permission']);

        $businessId = $this->businessId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $featureFlags = (array) $settings->feature_flags;

        if (array_key_exists($domain, $featureFlags) && ! $featureFlags[$domain]) {
            abort(404);
        }

        $summary = $this->vasUtil->enterpriseDomainSummary($businessId, $domain);
        $recentVouchers = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('module_area', $domain)
            ->latest('posting_date')
            ->latest('id')
            ->take(8)
            ->get();

        return view('vasaccounting::enterprise.index', [
            'domain' => $domain,
            'domainConfig' => $domainConfig,
            'summary' => $summary,
            'recentVouchers' => $recentVouchers,
        ]);
    }
}
