<?php

namespace Modules\ProjectX\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ProjectX\Entities\Trim;
use Modules\ProjectX\Http\Requests\DestroyTrimCategoryRequest;
use Modules\ProjectX\Http\Requests\StoreTrimRequest;
use Modules\ProjectX\Http\Requests\StoreTrimCategoryRequest;
use Modules\ProjectX\Http\Requests\UpdateTrimRequest;
use Modules\ProjectX\Http\Requests\UpdateTrimShareSettingsRequest;
use Modules\ProjectX\Utils\FabricCostingUtil;
use Modules\ProjectX\Utils\ProjectXNumberFormatUtil;
use Modules\ProjectX\Utils\ProjectXQuoteDisplayPresenter;
use Modules\ProjectX\Utils\QuoteUtil;
use Modules\ProjectX\Utils\TrimManagerUtil;

class TrimManagerController extends Controller
{
    protected TrimManagerUtil $trimUtil;

    protected QuoteUtil $quoteUtil;

    protected FabricCostingUtil $fabricCostingUtil;

    protected ProjectXQuoteDisplayPresenter $quoteDisplayPresenter;

    protected ProjectXNumberFormatUtil $numberFormatUtil;

    public function __construct(
        TrimManagerUtil $trimUtil,
        QuoteUtil $quoteUtil,
        FabricCostingUtil $fabricCostingUtil,
        ProjectXQuoteDisplayPresenter $quoteDisplayPresenter,
        ProjectXNumberFormatUtil $numberFormatUtil
    ) {
        $this->trimUtil = $trimUtil;
        $this->quoteUtil = $quoteUtil;
        $this->fabricCostingUtil = $fabricCostingUtil;
        $this->quoteDisplayPresenter = $quoteDisplayPresenter;
        $this->numberFormatUtil = $numberFormatUtil;

        $this->middleware(function ($request, $next) {
            if (! auth()->user()->can('projectx.trim.view')) {
                abort(403, __('projectx::lang.unauthorized_action'));
            }

            return $next($request);
        });
    }

    public function list(Request $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $status_filter = (string) $request->query('status', 'all');
        $category_filter = $request->query('category');

        $allowedStatuses = array_merge(['all'], Trim::STATUSES);
        if (! in_array($status_filter, $allowedStatuses, true)) {
            $status_filter = 'all';
        }

        if ($category_filter === '') {
            $category_filter = null;
        }

        $trims = $this->trimUtil->getTrims($business_id, $status_filter, $category_filter);
        $statusCounts = $this->trimUtil->getStatusCounts($business_id);
        $categories = $this->trimUtil->getCategoriesForBusiness($business_id);
        $currency = $request->session()->get('currency');

        return view('projectx::trims.list', compact(
            'trims',
            'statusCounts',
            'categories',
            'status_filter',
            'category_filter',
            'currency'
        ));
    }

    public function create(Request $request)
    {
        if (! auth()->user()->can('projectx.trim.create')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $categories = $this->trimUtil->getCategoriesForBusiness($business_id);
        $suppliers = Contact::suppliersDropdown($business_id, true, false);
        $currency = $request->session()->get('currency');

        return view('projectx::trims.create', compact('categories', 'suppliers', 'currency'));
    }

    public function storeCategory(StoreTrimCategoryRequest $request)
    {
        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $category = $this->trimUtil->createCategory($business_id, $request->validated());

            return $this->respondSuccess(__('projectx::lang.trim_category_added_success'), [
                'data' => [
                    'categories' => $this->formatTrimCategoriesForResponse(
                        $this->trimUtil->getCategoriesForBusiness($business_id)
                    ),
                    'selected_category_id' => (int) $category->id,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }
    }

    public function destroyCategory(DestroyTrimCategoryRequest $request, int $id)
    {
        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $affectedTrimCount = $this->trimUtil->deleteCategory($business_id, $id);

            return $this->respondSuccess(__('projectx::lang.trim_category_deleted_success'), [
                'data' => [
                    'categories' => $this->formatTrimCategoriesForResponse(
                        $this->trimUtil->getCategoriesForBusiness($business_id)
                    ),
                    'removed_category_id' => $id,
                    'affected_trim_count' => $affectedTrimCount,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }
    }

    public function store(StoreTrimRequest $request)
    {
        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $data = $request->validated();

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('trim_images', 'public');
            }

            DB::beginTransaction();
            $trim = $this->trimUtil->createTrim($business_id, $data);
            DB::commit();

            return redirect()
                ->route('projectx.trim_manager.show', ['id' => $trim->id])
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.trim_created_success')]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function show(Request $request, int $id)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $trim = $this->trimUtil->getTrimForBusiness($business_id, $id);
        $currency = $request->session()->get('currency');
        $activeTab = 'overview';

        return view('projectx::trims.show', compact('trim', 'currency', 'activeTab'));
    }

    public function edit(Request $request, int $id)
    {
        if (! auth()->user()->can('projectx.trim.edit')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $trim = $this->trimUtil->getTrimForBusiness($business_id, $id);
        $categories = $this->trimUtil->getCategoriesForBusiness($business_id);
        $suppliers = Contact::suppliersDropdown($business_id, true, false);
        $currency = $request->session()->get('currency');
        $activeTab = 'overview';

        return view('projectx::trims.edit', compact(
            'trim',
            'categories',
            'suppliers',
            'currency',
            'activeTab'
        ));
    }

    public function update(UpdateTrimRequest $request, int $id)
    {
        if (! auth()->user()->can('projectx.trim.edit')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $trim = $this->trimUtil->getTrimForBusiness($business_id, $id);
            $data = $request->validated();

            if ($request->hasFile('image')) {
                if (! empty($trim->image_path)) {
                    Storage::disk('public')->delete($trim->image_path);
                }
                $data['image_path'] = $request->file('image')->store('trim_images', 'public');
            }

            DB::beginTransaction();
            $trim = $this->trimUtil->updateTrim($business_id, $id, $data);
            DB::commit();

            $redirectRoute = $request->input('redirect_tab') === 'datasheet'
                ? 'projectx.trim_manager.datasheet'
                : 'projectx.trim_manager.show';

            return redirect()
                ->route($redirectRoute, ['id' => $trim->id])
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.trim_updated_success')]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function destroy(Request $request, int $id)
    {
        if (! auth()->user()->can('projectx.trim.delete')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $this->trimUtil->deleteTrim($business_id, $id);

            return redirect()
                ->route('projectx.trim_manager.list')
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.trim_deleted_success')]);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => $e->getMessage()]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function datasheet(Request $request, int $id)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $business = $this->resolveBusinessFromSession($request);
        $currencyPrecision = $this->numberFormatUtil->getCurrencyPrecision($business);
        $quantityPrecision = $this->numberFormatUtil->getQuantityPrecision($business);
        $trim = $this->trimUtil->getTrimForBusiness($business_id, $id);
        $shareSettings = $this->trimUtil->getShareSettings($trim);
        $fds = $this->trimUtil->buildTrimDatasheetPayload($trim);
        $fds['context'] = 'auth';
        $currency = $request->session()->get('currency');
        $activeTab = 'datasheet';
        $canEditTrimDatasheet = auth()->user()->can('projectx.trim.edit');
        $categories = $canEditTrimDatasheet
            ? $this->trimUtil->getCategoriesForBusiness($business_id)
            : collect();
        $suppliers = $canEditTrimDatasheet
            ? Contact::suppliersDropdown($business_id, true, false)
            : [];
        $uomOptions = $this->getTrimUomOptions();
        $statusOptions = $this->getTrimStatusOptions();
        $qcAtInputValue = $trim->qc_at ? $trim->qc_at->format('Y-m-d\\TH:i') : '';
        $approvedAtDisplay = $trim->approved_at
            ? $trim->approved_at->format('d M, Y H:i')
            : __('projectx::lang.not_set');
        $currencySymbol = (string) data_get($currency, 'symbol', '$');
        $defaultCurrencyCode = (string) ($trim->currency ?: data_get($currency, 'code', 'USD'));
        $currentTrimImageUrl = ! empty($trim->image_path)
            ? asset('storage/' . $trim->image_path)
            : null;
        $quantityFieldStep = $this->numberFormatUtil->stepFromPrecision($quantityPrecision);
        $currencyFieldStep = $this->numberFormatUtil->stepFromPrecision($currencyPrecision);
        $rateFieldStep = $this->numberFormatUtil->stepFromPrecision(4);
        $integerFieldStep = '1';
        $numberFieldMin = '0';
        $positiveQuantityFieldMin = $quantityPrecision > 0 ? $quantityFieldStep : '1';

        return view('projectx::trims.datasheet', compact(
            'trim',
            'shareSettings',
            'fds',
            'currency',
            'activeTab',
            'categories',
            'suppliers',
            'uomOptions',
            'statusOptions',
            'canEditTrimDatasheet',
            'qcAtInputValue',
            'approvedAtDisplay',
            'currencySymbol',
            'defaultCurrencyCode',
            'currentTrimImageUrl',
            'quantityFieldStep',
            'currencyFieldStep',
            'rateFieldStep',
            'integerFieldStep',
            'numberFieldMin',
            'positiveQuantityFieldMin'
        ));
    }

    public function datasheetPdf(Request $request, int $id)
    {
        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $trim = $this->trimUtil->getTrimForBusiness($business_id, $id);
            $fds = $this->trimUtil->buildTrimDatasheetPayload($trim);
            $fds['context'] = 'pdf';

            $html = view('projectx::trims.datasheet_pdf', compact('fds'))->render();
            $mpdf = $this->getMpdf();
            $mpdf->WriteHTML($html);

            $slug = Str::slug((string) ($trim->name ?: ('trim-' . $trim->id)));
            $fileName = 'Trim-Datasheet-' . $slug . '.pdf';

            return response($mpdf->Output($fileName, 'S'))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return redirect()
                ->route('projectx.trim_manager.datasheet', ['id' => $id])
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function updateShareSettings(UpdateTrimShareSettingsRequest $request, int $id)
    {
        if (! auth()->user()->can('projectx.trim.edit')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $data = $request->validated();
            $this->trimUtil->updateShareSettings($business_id, $id, $data);

            return redirect()
                ->route('projectx.trim_manager.datasheet', ['id' => $id])
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.share_settings_updated_success')]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function budget(Request $request, int $id)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $trim = $this->trimUtil->getTrimForBusiness($business_id, $id);
        $latestQuote = $this->quoteUtil->getLatestQuoteForTrim($business_id, $trim->id);
        $latestQuoteLine = null;
        if ($latestQuote) {
            $latestQuoteLine = $latestQuote->lines->firstWhere('trim_id', $trim->id)
                ?: $latestQuote->lines->first();
        }

        $customersDropdown = Contact::customersDropdown($business_id, false, true);
        $locationsDropdown = BusinessLocation::forDropdown($business_id, false, false);
        $costingDropdowns = $this->fabricCostingUtil->getDropdownOptions($business_id);
        $costingDropdowns['purchase_uom'] = array_values(array_unique(array_filter(array_merge(
            (array) ($costingDropdowns['purchase_uom'] ?? []),
            Trim::UOM_OPTIONS
        ))));

        $defaultBasePrice = (float) ($trim->unit_cost ?? 0);
        $defaultCurrencyCode = trim((string) ($trim->currency ?: $this->fabricCostingUtil->getDefaultCurrencyCode($business_id)));
        $defaultBasePriceInput = $this->numberFormatUtil->formatInput(
            $defaultBasePrice,
            $this->numberFormatUtil->getCurrencyPrecision($this->resolveBusinessFromSession($request))
        );
        $latestQuoteSummary = $this->quoteDisplayPresenter->presentLatestQuoteSummary($latestQuote, $latestQuoteLine);
        $latestQuoteRecipientEmail = (string) ($latestQuote
            ? ($latestQuote->customer_email ?: (optional($latestQuote->contact)->email ?? ''))
            : '');
        $currency = $request->session()->get('currency');
        $activeTab = 'budget';

        return view('projectx::trims.budget', compact(
            'trim',
            'latestQuote',
            'latestQuoteLine',
            'latestQuoteSummary',
            'latestQuoteRecipientEmail',
            'customersDropdown',
            'locationsDropdown',
            'costingDropdowns',
            'defaultBasePrice',
            'defaultBasePriceInput',
            'defaultCurrencyCode',
            'currency',
            'activeTab'
        ));
    }

    protected function formatTrimCategoriesForResponse($categories): array
    {
        return collect($categories)
            ->map(function ($category) {
                return [
                    'id' => (int) data_get($category, 'id'),
                    'name' => (string) data_get($category, 'name'),
                ];
            })
            ->values()
            ->all();
    }

    protected function getTrimUomOptions(): array
    {
        return [
            ['value' => 'pcs', 'label' => __('projectx::lang.uom_pcs')],
            ['value' => 'cm', 'label' => __('projectx::lang.uom_cm')],
            ['value' => 'inches', 'label' => __('projectx::lang.uom_inches')],
            ['value' => 'yards', 'label' => __('projectx::lang.uom_yards')],
            ['value' => 'sets', 'label' => __('projectx::lang.uom_sets')],
            ['value' => 'gross', 'label' => __('projectx::lang.uom_gross')],
            ['value' => 'gg', 'label' => __('projectx::lang.uom_gg')],
        ];
    }

    protected function getTrimStatusOptions(): array
    {
        return [
            ['value' => Trim::STATUS_DRAFT, 'label' => __('projectx::lang.trim_status_draft')],
            ['value' => Trim::STATUS_SAMPLE_REQUESTED, 'label' => __('projectx::lang.trim_status_sample_requested')],
            ['value' => Trim::STATUS_SAMPLE_RECEIVED, 'label' => __('projectx::lang.trim_status_sample_received')],
            ['value' => Trim::STATUS_APPROVED, 'label' => __('projectx::lang.trim_status_approved')],
            ['value' => Trim::STATUS_BULK_ORDERED, 'label' => __('projectx::lang.trim_status_bulk_ordered')],
            ['value' => Trim::STATUS_BULK_RECEIVED, 'label' => __('projectx::lang.trim_status_bulk_received')],
            ['value' => Trim::STATUS_QC_PASSED, 'label' => __('projectx::lang.trim_status_qc_passed')],
            ['value' => Trim::STATUS_QC_FAILED, 'label' => __('projectx::lang.trim_status_qc_failed')],
        ];
    }

    protected function resolveBusinessFromSession(Request $request): ?object
    {
        $business = $request->session()->get('business');
        if (is_object($business)) {
            return $business;
        }

        if (is_array($business)) {
            return (object) $business;
        }

        return null;
    }
}
