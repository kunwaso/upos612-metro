<?php

namespace Modules\ProjectX\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ProjectX\Entities\Fabric;
use Modules\ProjectX\Entities\FabricActivityLog;
use Modules\ProjectX\Entities\Quote;
use Modules\ProjectX\Http\Requests\DeleteFabricAttachmentRequest;
use Modules\ProjectX\Http\Requests\DestroyFabricActivityLogRequest;
use Modules\ProjectX\Http\Requests\StoreFabricAttachmentRequest;
use Modules\ProjectX\Http\Requests\StoreFabricRequest;
use Modules\ProjectX\Http\Requests\UpdateFabricCompositionRequest;
use Modules\ProjectX\Http\Requests\UpdateFabricPantoneRequest;
use Modules\ProjectX\Http\Requests\UpdateFabricShareSettingsRequest;
use Modules\ProjectX\Http\Requests\UpdateFabricSettingsRequest;
use Modules\ProjectX\Utils\FabricActivityLogUtil;
use Modules\ProjectX\Utils\FabricCostingUtil;
use Modules\ProjectX\Utils\FabricManagerUtil;
use Modules\ProjectX\Utils\FabricProductSyncUtil;
use Modules\ProjectX\Utils\ProjectXNumberFormatUtil;
use Modules\ProjectX\Utils\ProjectXQuoteDisplayPresenter;

class FabricManagerController extends Controller
{
    protected $fabricUtil;
    protected $activityLogUtil;
    protected $fabricCostingUtil;
    protected $fabricProductSyncUtil;
    protected ProjectXQuoteDisplayPresenter $quoteDisplayPresenter;
    protected ProjectXNumberFormatUtil $numberFormatUtil;

    public function __construct(
        FabricManagerUtil $fabricUtil,
        FabricActivityLogUtil $activityLogUtil,
        FabricCostingUtil $fabricCostingUtil,
        FabricProductSyncUtil $fabricProductSyncUtil,
        ProjectXQuoteDisplayPresenter $quoteDisplayPresenter,
        ProjectXNumberFormatUtil $numberFormatUtil
    )
    {
        $this->fabricUtil = $fabricUtil;
        $this->activityLogUtil = $activityLogUtil;
        $this->fabricCostingUtil = $fabricCostingUtil;
        $this->fabricProductSyncUtil = $fabricProductSyncUtil;
        $this->quoteDisplayPresenter = $quoteDisplayPresenter;
        $this->numberFormatUtil = $numberFormatUtil;

        $this->middleware(function ($request, $next) {
            if (! auth()->user()->can('product.view')) {
                abort(403, __('projectx::lang.unauthorized_action'));
            }

            return $next($request);
        });
    }

    public function list(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $status_filter = $request->query('status', 'all');
        $allowed_statuses = array_merge(['all'], Fabric::STATUSES);
        if (! in_array($status_filter, $allowed_statuses, true)) {
            $status_filter = 'all';
        }

        $fabrics = $this->fabricUtil->getFabrics($business_id, $status_filter);
        foreach ($fabrics as $fabric) {
            $fabric->compositionView = $this->fabricUtil->getCompositionViewForFabric($fabric);
            $fabric->primarySupplier = $fabric->suppliers->first() ?: $fabric->supplier;
            $fabric->supplierCount = $fabric->suppliers->isNotEmpty()
                ? $fabric->suppliers->count()
                : ($fabric->primarySupplier ? 1 : 0);
        }
        $statusCounts = $this->fabricUtil->getStatusCounts($business_id);
        $financeMetrics = $this->fabricUtil->getFinanceMetrics($business_id);
        $supplierSnapshot = $this->fabricUtil->getSupplierSnapshot($business_id);

        $currency = $request->session()->get('currency');
        return view('projectx::fabric_manager.list', compact(
            'fabrics',
            'statusCounts',
            'financeMetrics',
            'supplierSnapshot',
            'status_filter',
            'currency'
        ));
    }

    public function create(Request $request)
    {
        if (! auth()->user()->can('product.create')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = $request->session()->get('user.business_id');
        $suppliers = Contact::suppliersDropdown($business_id, true, false);
        $currency = $request->session()->get('currency');

        return view('projectx::fabric_manager.create', compact('suppliers', 'currency'));
    }

    public function store(StoreFabricRequest $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $data = $request->validated();

            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('fabric_images', 'public');
            }

            $data['created_by'] = auth()->user()->id;

            DB::beginTransaction();
            $fabric = $this->fabricUtil->createFabric($business_id, $data);
            $this->activityLogUtil->log(
                $business_id,
                $fabric->id,
                FabricActivityLog::ACTION_FABRIC_CREATED,
                null,
                (int) auth()->user()->id,
                ['fabric_name' => $fabric->name]
            );
            DB::commit();

            return redirect()
                ->route('projectx.fabric_manager.list')
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.fabric_created_success')]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function fabric(Request $request, int $fabric_id)
    {
        return $this->renderFabricTab($request, $fabric_id, 'overview', 'projectx::fabric_manager.fabric');
    }

    public function datasheet(Request $request, int $fabric_id)
    {
        return $this->renderFabricTab($request, $fabric_id, 'datasheet', 'projectx::fabric_manager.datasheet');
    }

    public function budget(Request $request, int $fabric_id)
    {
        return $this->renderFabricTab($request, $fabric_id, 'budget', 'projectx::fabric_manager.budget');
    }

    public function users(Request $request, int $fabric_id)
    {
        return $this->renderFabricTab($request, $fabric_id, 'users', 'projectx::fabric_manager.users');
    }

    public function files(Request $request, int $fabric_id)
    {
        return $this->renderFabricTab($request, $fabric_id, 'files', 'projectx::fabric_manager.files');
    }

    public function uploadAttachment(StoreFabricAttachmentRequest $request, int $fabric_id)
    {
        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $attachments = $request->file('attachments', []);

            $this->fabricUtil->appendAttachmentsToFabric(
                $business_id,
                $fabric_id,
                is_array($attachments) ? $attachments : [],
                (int) auth()->id()
            );

            return $this->redirectAfterFileAction(
                $business_id,
                $fabric_id,
                (int) $request->input('redirect_to_product_id', 0),
                __('projectx::lang.fabric_attachment_upload_success')
            );
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

    public function downloadAttachment(Request $request, int $fabric_id, string $file_hash)
    {
        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $attachment = $this->fabricUtil->getAttachmentByHashForFabric($business_id, $fabric_id, $file_hash);
            $path = (string) $attachment['path'];
            $name = (string) $attachment['name'];

            if (! Storage::disk('public')->exists($path)) {
                abort(404, __('projectx::lang.fabric_attachment_not_found'));
            }

            return Storage::disk('public')->download($path, $name);
        } catch (\InvalidArgumentException $e) {
            abort(404, $e->getMessage());
        }
    }

    public function destroyAttachment(DeleteFabricAttachmentRequest $request, int $fabric_id, string $file_hash)
    {
        try {
            $business_id = (int) $request->session()->get('user.business_id');

            $this->fabricUtil->deleteAttachmentFromFabric(
                $business_id,
                $fabric_id,
                $file_hash,
                (int) auth()->id()
            );

            return $this->redirectAfterFileAction(
                $business_id,
                $fabric_id,
                (int) $request->input('redirect_to_product_id', 0),
                __('projectx::lang.fabric_attachment_delete_success')
            );
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

    public function activity(Request $request, int $fabric_id)
    {
        return $this->renderFabricTab($request, $fabric_id, 'activity', 'projectx::fabric_manager.activity');
    }

    public function settings(Request $request, int $fabric_id)
    {
        return $this->renderFabricTab($request, $fabric_id, 'settings', 'projectx::fabric_manager.settings');
    }

    public function updateSettings(UpdateFabricSettingsRequest $request, int $fabric_id)
    {
        if (! auth()->user()->can('projectx.fabric.create') && ! auth()->user()->can('product.create')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $data = $request->validated();
            $attachments = $request->file('attachments', []);

            DB::beginTransaction();
            $fabric = $this->fabricUtil->updateFabricSettings(
                $business_id,
                $fabric_id,
                $data,
                $request->file('image'),
                is_array($attachments) ? $attachments : [],
                (int) auth()->user()->id
            );
            $this->fabricProductSyncUtil->syncProductFromFabric($business_id, $fabric);
            DB::commit();

            $redirectRoute = $request->input('redirect_tab') === 'datasheet'
                ? 'projectx.fabric_manager.datasheet'
                : 'projectx.fabric_manager.settings';

            return redirect()
                ->route($redirectRoute, ['fabric_id' => $fabric->id])
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.fabric_updated_success')]);
        } catch (\RuntimeException $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $e->getMessage()]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function datasheetPdf(Request $request, int $fabric_id)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $fabric = $this->fabricUtil->getFabricById($business_id, $fabric_id);
            $fds = $this->fabricUtil->buildDatasheetPayload($fabric);
            $fds['context'] = 'pdf';

            $html = view('projectx::fabric_manager.datasheet_pdf', compact('fds'))->render();
            $mpdf = $this->getMpdf();
            $mpdf->WriteHTML($html);

            $slug = Str::slug((string) ($fabric->name ?: ('fabric-' . $fabric->id)));
            $fileName = 'Fabric-Datasheet-' . $slug . '.pdf';

            return response($mpdf->Output($fileName, 'S'))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return redirect()
                ->route('projectx.fabric_manager.datasheet', ['fabric_id' => $fabric_id])
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function updateShareSettings(UpdateFabricShareSettingsRequest $request, int $fabric_id)
    {
        if (! auth()->user()->can('projectx.fabric.create') && ! auth()->user()->can('product.create')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $data = $request->validated();

            DB::beginTransaction();
            $this->fabricUtil->updateShareSettings(
                $business_id,
                $fabric_id,
                $data,
                (int) auth()->user()->id
            );
            DB::commit();

            return redirect()
                ->route('projectx.fabric_manager.datasheet', ['fabric_id' => $fabric_id])
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.share_settings_updated_success')]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function submitForApproval(Request $request, int $fabric_id)
    {
        return $this->handleStatusTransition(
            $request,
            $fabric_id,
            [Fabric::STATUS_DRAFT],
            Fabric::STATUS_NEEDS_APPROVAL,
            ['projectx.fabric.submit', 'product.create'],
            FabricActivityLog::ACTION_SUBMITTED_FOR_APPROVAL,
            __('projectx::lang.status_submitted_success')
        );
    }

    public function approve(Request $request, int $fabric_id)
    {
        return $this->handleStatusTransition(
            $request,
            $fabric_id,
            [Fabric::STATUS_NEEDS_APPROVAL],
            Fabric::STATUS_ACTIVE,
            ['projectx.fabric.approve', 'product.create'],
            FabricActivityLog::ACTION_APPROVED,
            __('projectx::lang.status_approved_success')
        );
    }

    public function reject(Request $request, int $fabric_id)
    {
        return $this->handleStatusTransition(
            $request,
            $fabric_id,
            [Fabric::STATUS_NEEDS_APPROVAL],
            Fabric::STATUS_REJECTED,
            ['projectx.fabric.reject', 'product.create'],
            FabricActivityLog::ACTION_REJECTED,
            __('projectx::lang.status_rejected_success')
        );
    }

    public function getComposition(Request $request, int $fabric_id)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $composition = $this->fabricUtil->getCompositionPayload($business_id, $fabric_id);

            return $this->respondSuccess(__('lang_v.success'), $composition);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }
    }

    public function updateComposition(UpdateFabricCompositionRequest $request, int $fabric_id)
    {
        if (! auth()->user()->can('product.create')) {
            return $this->respondUnauthorized(__('projectx::lang.unauthorized_action'));
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $data = $request->validated();

            $composition = $this->fabricUtil->updateComposition(
                $business_id,
                $fabric_id,
                $data['items'],
                (int) auth()->user()->id
            );

            return $this->respondSuccess(__('lang_v.success'), $composition);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }
    }

    public function getComponentCatalog(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $catalog = $this->fabricUtil->getComponentCatalog($business_id)
                ->map(function ($component) {
                    return [
                        'id' => (int) $component->id,
                        'label' => $component->label,
                        'aliases' => $component->aliases ?? [],
                    ];
                })
                ->values()
                ->all();

            return $this->respondSuccess(__('lang_v.success'), ['catalog' => $catalog]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }
    }

    public function getPantone(Request $request, int $fabric_id)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $items = $this->fabricUtil->getPantoneForFabric($business_id, $fabric_id);

            return $this->respondSuccess(__('lang_v.success'), ['items' => $items]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }
    }

    public function updatePantone(UpdateFabricPantoneRequest $request, int $fabric_id)
    {
        if (! auth()->user()->can('product.create')) {
            return $this->respondUnauthorized(__('projectx::lang.unauthorized_action'));
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $validated = $request->validated();
            $items = array_values(array_unique(array_filter(array_map(function ($code) {
                return trim((string) $code);
            }, $validated['items'] ?? []))));
            $pantone = $this->fabricUtil->updatePantoneList(
                $business_id,
                $fabric_id,
                $items,
                (int) auth()->user()->id
            );

            return $this->respondSuccess(__('lang_v.success'), ['items' => $pantone]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }
    }

    public function getPantoneTcxCatalog(Request $request)
    {
        try {
            $catalog = $this->fabricUtil->getPantoneTcxCatalog();
            $list = [];
            foreach ($catalog as $code => $info) {
                $list[] = [
                    'code' => $code,
                    'hex' => $info['hex'] ?? '',
                    'name' => $info['name'] ?? $code,
                ];
            }

            return $this->respondSuccess(__('lang_v.success'), ['catalog' => $list]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }
    }

    public function destroyActivityLog(DestroyFabricActivityLogRequest $request, int $fabric_id, int $log_id)
    {
        if (! $this->canDeleteFabricActivity()) {
            return $this->respondUnauthorized(__('projectx::lang.unauthorized_action'));
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $this->activityLogUtil->deleteLog($business_id, $fabric_id, $log_id);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('projectx::lang.activity_deleted_success'));
            }

            return redirect()
                ->route('projectx.fabric_manager.activity', ['fabric_id' => $fabric_id])
                ->with('status', ['success' => true, 'msg' => __('projectx::lang.activity_deleted_success')]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($e);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    protected function renderFabricTab(Request $request, int $fabric_id, string $activeTab, string $view)
    {
        $business_id = $request->session()->get('user.business_id');
        $fabric = $this->fabricUtil->getFabricById($business_id, $fabric_id);
        $currency = $request->session()->get('currency');
        $compositionSummary = $this->fabricUtil->getComponentSummaryForFabric($fabric);
        $headerFabricImage = ! empty($fabric->image_path)
            ? asset('storage/' . $fabric->image_path)
            : asset('modules/projectx/media/svg/brand-logos/volicity-9.svg');
        $descriptionParts = array_filter([
            $compositionSummary !== null && $compositionSummary !== '-' ? $compositionSummary : null,
            $fabric->fiber ?? null,
        ]);
        $headerFabricDescription = ! empty($descriptionParts)
            ? implode(' / ', $descriptionParts)
            : __('projectx::lang.no_composition_fiber');
        $businessDateFormat = (string) ($request->session()->get('business.date_format') ?: 'Y-m-d');
        $headerFabricCreatedAt = ! empty($fabric->created_at)
            ? \Carbon\Carbon::createFromTimestamp(strtotime($fabric->created_at))->format($businessDateFormat)
            : '-';
        $headerPrimarySupplier = $fabric->suppliers->first() ?: $fabric->supplier;
        $headerSupplierCount = $fabric->suppliers->isNotEmpty()
            ? $fabric->suppliers->count()
            : ($headerPrimarySupplier ? 1 : 0);
        $viewData = compact(
            'activeTab',
            'fabric',
            'currency',
            'compositionSummary',
            'headerFabricImage',
            'headerFabricDescription',
            'headerFabricCreatedAt',
            'headerPrimarySupplier',
            'headerSupplierCount'
        );

        if ($view === 'projectx::fabric_manager.fabric') {
            $composition = $this->fabricUtil->getCompositionPayload($business_id, $fabric_id);
            $componentCatalog = $this->fabricUtil->getComponentCatalog($business_id)
                ->map(function ($component) {
                    return [
                        'id' => (int) $component->id,
                        'label' => $component->label,
                        'aliases' => $component->aliases ?? [],
                    ];
                })
                ->values()
                ->all();

            $compositionView = [
                'items' => $composition['items'] ?? [],
                'count' => $composition['composition_count'] ?? 0,
                'chart' => $composition['chart'] ?? ['labels' => [], 'data' => [], 'colors' => []],
            ];

            $compositionFrontendConfig = [
                'fetchUrl' => route('projectx.fabric_manager.composition.show', ['fabric_id' => $fabric->id]),
                'updateUrl' => route('projectx.fabric_manager.composition.update', ['fabric_id' => $fabric->id]),
                'catalogUrl' => route('projectx.fabric_manager.component_catalog'),
                'initialComposition' => $composition,
                'initialCatalog' => $componentCatalog,
                'messages' => $this->getCompositionFrontendMessages(),
            ];

            $pantoneView = $this->fabricUtil->getPantoneForFabric($business_id, $fabric_id);
            $pantoneFrontendConfig = [
                'fetchUrl' => route('projectx.fabric_manager.pantone.show', ['fabric_id' => $fabric->id]),
                'updateUrl' => route('projectx.fabric_manager.pantone.update', ['fabric_id' => $fabric->id]),
                'catalogUrl' => route('projectx.fabric_manager.pantone_catalog'),
            ];

            $viewData = array_merge($viewData, compact(
                'compositionView',
                'compositionFrontendConfig',
                'pantoneView',
                'pantoneFrontendConfig'
            ));
        }

        if ($view === 'projectx::fabric_manager.budget') {
            $customersDropdown = Contact::customersDropdown($business_id, false, true);
            $locationsDropdown = BusinessLocation::forDropdown($business_id, false, false);
            $costingDropdowns = $this->fabricCostingUtil->getDropdownOptions($business_id);
            $defaultCurrencyCode = $this->fabricCostingUtil->getDefaultCurrencyCode($business_id);
            $defaultBasePrice = (float) ($fabric->price_500_yds ?? 0);
            $business = $this->resolveBusinessFromSession($request);
            $currencyPrecision = $this->numberFormatUtil->getCurrencyPrecision($business);
            $defaultBasePriceInput = $this->numberFormatUtil->formatInput($defaultBasePrice, $currencyPrecision);

            $quoteQuery = Quote::forBusiness($business_id)
                ->whereHas('lines', function ($query) use ($fabric_id) {
                    $query->where('fabric_id', $fabric_id);
                })
                ->with([
                    'contact:id,name,supplier_business_name,email',
                    'location:id,name',
                    'transaction:id,invoice_no,status',
                    'lines' => function ($query) {
                        $query->orderBy('sort_order')->orderBy('id');
                    },
                    'lines.fabric:id,name,fabric_sku,mill_article_no',
                ])
                ->orderByDesc('id');

            $selectedQuoteId = (int) $request->query('quote_id', 0);
            $selectedQuote = null;
            if ($selectedQuoteId > 0) {
                $selectedQuote = (clone $quoteQuery)
                    ->where('projectx_quotes.id', $selectedQuoteId)
                    ->first();
            }

            $latestQuote = $selectedQuote ?: (clone $quoteQuery)->first();
            $latestQuoteLine = null;
            if ($latestQuote) {
                $latestQuoteLine = $latestQuote->lines->firstWhere('fabric_id', $fabric_id)
                    ?: $latestQuote->lines->first();
            }
            $latestQuoteSummary = $this->quoteDisplayPresenter->presentLatestQuoteSummary($latestQuote, $latestQuoteLine);
            $latestQuoteRecipientEmail = (string) ($latestQuote
                ? ($latestQuote->customer_email ?: (optional($latestQuote->contact)->email ?? ''))
                : '');

            $viewData = array_merge($viewData, compact(
                'customersDropdown',
                'locationsDropdown',
                'costingDropdowns',
                'defaultCurrencyCode',
                'defaultBasePrice',
                'defaultBasePriceInput',
                'latestQuote',
                'latestQuoteLine',
                'latestQuoteSummary',
                'latestQuoteRecipientEmail'
            ));
        }

        if (in_array($view, ['projectx::fabric_manager.settings', 'projectx::fabric_manager.datasheet'], true)) {
            $composition = $this->fabricUtil->getCompositionPayload($business_id, $fabric_id);
            $suppliers = Contact::suppliersDropdown($business_id, false, true);
            $selectedSupplierIds = $fabric->suppliers
                ->pluck('id')
                ->map(function ($supplierId) {
                    return (string) $supplierId;
                })
                ->values()
                ->all();
            $selectedSupplierIdsForForm = $this->normalizeStringArray(old('supplier_contact_ids', $selectedSupplierIds));
            $compositionView = [
                'items' => $composition['items'] ?? [],
                'count' => $composition['composition_count'] ?? 0,
                'chart' => $composition['chart'] ?? ['labels' => [], 'data' => [], 'colors' => []],
            ];
            $pantoneItems = $this->fabricUtil->getPantoneForFabric($business_id, $fabric_id);
            $shareSettings = $this->fabricUtil->getShareSettings($fabric);
            $fdsDatalists = $this->fabricUtil->getFdsDatalists();
            $millPatternColorsForForm = $fabric->mill_pattern_color;
            if (! is_array($millPatternColorsForForm)) {
                $millPatternColorsForForm = $millPatternColorsForForm === null || $millPatternColorsForForm === ''
                    ? []
                    : [(string) $millPatternColorsForForm];
            }
            if (empty($millPatternColorsForForm)) {
                $millPatternColorsForForm = [''];
            }

            $viewData = array_merge($viewData, compact(
                'compositionView',
                'suppliers',
                'selectedSupplierIds',
                'selectedSupplierIdsForForm',
                'pantoneItems',
                'shareSettings',
                'fdsDatalists',
                'millPatternColorsForForm'
            ));
        }

        if ($view === 'projectx::fabric_manager.activity') {
            $activityToday = $this->activityLogUtil->getForFabric($business_id, $fabric_id, FabricActivityLog::PERIOD_TODAY);
            $activityWeek = $this->activityLogUtil->getForFabric($business_id, $fabric_id, FabricActivityLog::PERIOD_WEEK);
            $activityMonth = $this->activityLogUtil->getForFabric($business_id, $fabric_id, FabricActivityLog::PERIOD_MONTH);
            $activityYear = $this->activityLogUtil->getForFabric($business_id, $fabric_id, FabricActivityLog::PERIOD_YEAR);
            $activityYearLabel = (int) now()->year;
            $canDeleteActivity = $this->canDeleteFabricActivity();

            $viewData = array_merge($viewData, compact(
                'activityToday',
                'activityWeek',
                'activityMonth',
                'activityYear',
                'activityYearLabel',
                'canDeleteActivity'
            ));
        }

        return $this->renderFabricTabResponse($request, $view, $viewData, $fabric_id, $activeTab);
    }

    protected function renderFabricTabResponse(
        Request $request,
        string $view,
        array $viewData,
        int $fabric_id,
        string $activeTab
    ) {
        if (! $this->shouldReturnFabricTabPartial($request)) {
            return view($view, $viewData);
        }

        $renderedView = view($view, $viewData);
        $sections = $renderedView->renderSections();

        return $this->respondSuccess(__('lang_v.success'), [
            'data' => [
                'content_html' => (string) ($sections['content'] ?? ''),
                'page_javascript_html' => (string) ($sections['page_javascript'] ?? ''),
                'title' => trim((string) ($sections['title'] ?? '')),
                'fabric_id' => $fabric_id,
                'active_tab' => $activeTab,
                'url' => $request->fullUrl(),
            ],
        ]);
    }

    protected function shouldReturnFabricTabPartial(Request $request): bool
    {
        if (strtolower((string) $request->header('X-ProjectX-Partial')) !== 'fabric-tab') {
            return false;
        }

        return $request->ajax()
            || $request->expectsJson()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';
    }

    protected function handleStatusTransition(
        Request $request,
        int $fabric_id,
        array $fromStatuses,
        string $toStatus,
        array $permissions,
        string $activityAction,
        string $successMessage
    ) {
        if (! $this->userHasAnyPermission($permissions)) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            DB::beginTransaction();
            $fabric = $this->fabricUtil->transitionStatus(
                $business_id,
                $fabric_id,
                $fromStatuses,
                $toStatus,
                (int) auth()->user()->id,
                $activityAction
            );
            DB::commit();

            return redirect()
                ->route('projectx.fabric_manager.datasheet', ['fabric_id' => $fabric->id])
                ->with('status', ['success' => true, 'msg' => $successMessage]);
        } catch (\RuntimeException $e) {
            DB::rollBack();

            return redirect()
                ->route('projectx.fabric_manager.datasheet', ['fabric_id' => $fabric_id])
                ->with('status', ['success' => false, 'msg' => $e->getMessage()]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return redirect()
                ->route('projectx.fabric_manager.datasheet', ['fabric_id' => $fabric_id])
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    protected function userHasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (auth()->user()->can($permission)) {
                return true;
            }
        }

        return false;
    }

    protected function getCompositionFrontendMessages(): array
    {
        return [
            'noCompositions' => __('projectx::lang.no_compositions_added'),
            'compositionSingular' => __('projectx::lang.composition_word_singular'),
            'compositionPlural' => __('projectx::lang.composition_word_plural'),
            'compositionName' => __('projectx::lang.composition_name'),
            'compositionPercent' => __('projectx::lang.composition_percent'),
            'compositionCustomLabel' => __('projectx::lang.composition_custom_label'),
            'removeComposition' => __('projectx::lang.remove_composition'),
            'compositionTotal' => __('projectx::lang.composition_total'),
            'compositionTotalWarning' => __('projectx::lang.composition_total_not_100_warning'),
            'compositionRequiredError' => __('projectx::lang.composition_required_error'),
            'compositionPercentError' => __('projectx::lang.composition_percent_error'),
            'compositionOtherLabelRequired' => __('projectx::lang.composition_other_label_required'),
            'compositionDuplicateError' => __('projectx::lang.composition_duplicate_error'),
            'compositionItemsRequired' => __('projectx::lang.composition_items_required'),
            'compositionSaved' => __('projectx::lang.composition_saved'),
            'somethingWentWrong' => __('messages.something_went_wrong'),
        ];
    }

    protected function canDeleteFabricActivity(): bool
    {
        return auth()->user()->can('superadmin')
            || auth()->user()->can('projectx.fabric.activity.delete');
    }

    protected function redirectAfterFileAction(
        int $business_id,
        int $fabric_id,
        int $redirectProductId,
        string $message
    ) {
        if ($redirectProductId > 0) {
            $linkedFabric = $this->fabricProductSyncUtil->findLinkedFabric($business_id, $redirectProductId);

            if (! empty($linkedFabric) && (int) $linkedFabric->id === $fabric_id) {
                return redirect()
                    ->route('product.detail', ['id' => $redirectProductId, 'tab' => 'files'])
                    ->with('status', ['success' => true, 'msg' => $message]);
            }
        }

        return redirect()
            ->route('projectx.fabric_manager.files', ['fabric_id' => $fabric_id])
            ->with('status', ['success' => true, 'msg' => $message]);
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

    protected function normalizeStringArray($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map('strval', $value));
    }
}
