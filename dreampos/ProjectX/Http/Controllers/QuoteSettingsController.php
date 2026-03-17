<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Business;
use App\Http\Controllers\Controller;
use App\Utils\BusinessUtil;
use Illuminate\Http\Request;
use Modules\ProjectX\Entities\QuoteSetting;
use Modules\ProjectX\Http\Requests\UpdateQuoteSettingsRequest;
use Modules\ProjectX\Utils\QuoteUtil;

class QuoteSettingsController extends Controller
{
    protected QuoteUtil $quoteUtil;
    protected BusinessUtil $businessUtil;

    public function __construct(QuoteUtil $quoteUtil, BusinessUtil $businessUtil)
    {
        $this->quoteUtil = $quoteUtil;
        $this->businessUtil = $businessUtil;
    }

    public function edit(Request $request)
    {
        if (! auth()->user()->can('projectx.quote.edit')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $currentPrefix = $this->quoteUtil->getQuotePrefix($business_id);
        $business = Business::findOrFail($business_id, ['id', 'currency_id']);
        $quoteSetting = QuoteSetting::firstOrNew(['business_id' => $business_id]);
        $currencies = $this->businessUtil->allCurrencies();
        $defaultCurrencyId = ! empty($quoteSetting->default_currency_id)
            ? (int) $quoteSetting->default_currency_id
            : null;
        $effectiveDefaultCurrencyId = $defaultCurrencyId ?: (int) $business->currency_id;

        $incotermOptions = (array) ($quoteSetting->incoterm_options ?? []);
        $purchaseUomOptions = (array) ($quoteSetting->purchase_uom_options ?? []);
        $isIncotermFallback = empty($incotermOptions);
        $isPurchaseUomFallback = empty($purchaseUomOptions);

        if ($isIncotermFallback) {
            $incotermOptions = config('projectx.quote_costing_options.incoterm', []);
        }

        if ($isPurchaseUomFallback) {
            $purchaseUomOptions = config('projectx.quote_costing_options.purchase_uom', []);
        }

        return view('projectx::settings.quotes', compact(
            'currentPrefix',
            'quoteSetting',
            'currencies',
            'defaultCurrencyId',
            'effectiveDefaultCurrencyId',
            'incotermOptions',
            'purchaseUomOptions',
            'isIncotermFallback',
            'isPurchaseUomFallback'
        ));
    }

    public function update(UpdateQuoteSettingsRequest $request)
    {
        if (! auth()->user()->can('projectx.quote.edit')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $business = Business::findOrFail($business_id);

            $validated = $request->validated();
            $prefix = trim((string) ($validated['prefix'] ?? ''));
            if ($prefix === '') {
                $prefix = 'RFQ';
            }
            $defaultCurrencyId = ! empty($validated['default_currency_id'])
                ? (int) $validated['default_currency_id']
                : null;

            $refNoPrefixes = (array) ($business->ref_no_prefixes ?? []);
            $refNoPrefixes['projectx_quote'] = $prefix;

            $business->ref_no_prefixes = $refNoPrefixes;
            $business->save();

            $incotermOptions = array_values(array_filter(array_map('trim', (array) ($validated['incoterm_options'] ?? []))));
            $purchaseUomOptions = array_values(array_filter(array_map('trim', (array) ($validated['purchase_uom_options'] ?? []))));

            QuoteSetting::updateOrCreate(
                ['business_id' => $business_id],
                [
                    'default_currency_id' => $defaultCurrencyId,
                    'incoterm_options' => ! empty($incotermOptions) ? $incotermOptions : null,
                    'purchase_uom_options' => ! empty($purchaseUomOptions) ? $purchaseUomOptions : null,
                ]
            );

            $sessionBusiness = (array) $request->session()->get('business');
            $sessionBusiness['ref_no_prefixes'] = $refNoPrefixes;
            $request->session()->put('business', $sessionBusiness);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('projectx::lang.quote_settings_updated'));
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.quote_settings_updated')]);
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($e);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }
}
