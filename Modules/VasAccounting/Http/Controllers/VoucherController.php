<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Http\Requests\StoreManualVoucherRequest;
use Modules\VasAccounting\Services\VasPostingService;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;
use Throwable;

class VoucherController extends VasBaseController
{
    public function __construct(
        protected VasPostingService $postingService,
        protected VasAccountingUtil $vasUtil
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.vouchers.manage');

        $businessId = $this->businessId($request);
        $vouchers = VasVoucher::query()
            ->where('business_id', $businessId)
            ->latest('posting_date')
            ->latest('id')
            ->paginate(20);

        return view('vasaccounting::vouchers.index', compact('vouchers'));
    }

    public function create(Request $request)
    {
        $this->authorizePermission('vas_accounting.vouchers.manage');

        $businessId = $this->businessId($request);
        $accounts = $this->vasUtil->chartOptions($businessId);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $enterpriseDomains = $this->vasUtil->enterpriseDomains();
        $documentStatuses = $this->vasUtil->documentStatuses();

        return view('vasaccounting::vouchers.create', compact('accounts', 'settings', 'enterpriseDomains', 'documentStatuses'));
    }

    public function store(StoreManualVoucherRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $payload = $request->validated();

        $voucher = $this->postingService->postVoucherPayload([
            'business_id' => $businessId,
            'voucher_type' => $payload['voucher_type'],
            'sequence_key' => 'general_journal',
            'source_type' => 'manual',
            'source_id' => null,
            'module_area' => $payload['module_area'] ?? 'accounting',
            'document_type' => $payload['document_type'] ?? $payload['voucher_type'],
            'posting_date' => $payload['posting_date'],
            'document_date' => $payload['document_date'],
            'description' => $payload['description'] ?? null,
            'reference' => $payload['reference'] ?? null,
            'status' => $payload['status'] ?? data_get($this->vasUtil->getOrCreateBusinessSettings($businessId)->approval_settings, 'default_manual_voucher_status', 'draft'),
            'currency_code' => 'VND',
            'created_by' => (int) auth()->id(),
            'is_system_generated' => false,
            'lines' => $payload['lines'],
        ]);

        return redirect()
            ->route('vasaccounting.vouchers.show', $voucher->id)
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.manual_voucher_saved')]);
    }

    public function show(Request $request, int $voucher)
    {
        $this->authorizePermission('vas_accounting.vouchers.manage');

        $voucher = VasVoucher::query()
            ->where('business_id', $this->businessId($request))
            ->with(['lines.account', 'period'])
            ->findOrFail($voucher);

        $deleteDraftEligibility = $this->postingService->draftVoucherDeletionStatus($voucher);
        $reversalEligibility = $this->postingService->voucherReversalStatus($voucher);

        return view('vasaccounting::vouchers.show', compact('voucher', 'deleteDraftEligibility', 'reversalEligibility'));
    }

    public function post(Request $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.vouchers.manage');

        $voucherModel = VasVoucher::query()
            ->where('business_id', $this->businessId($request))
            ->with('lines')
            ->findOrFail($voucher);

        $voucherModel = $this->postingService->postExistingVoucher($voucherModel, (int) auth()->id());

        return redirect()
            ->route('vasaccounting.vouchers.show', $voucherModel->id)
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.manual_voucher_posted')]);
    }

    public function reverse(Request $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.vouchers.manage');

        $voucherModel = VasVoucher::query()
            ->where('business_id', $this->businessId($request))
            ->with('lines')
            ->findOrFail($voucher);

        try {
            $reversal = $this->postingService->reverseVoucher($voucherModel, (int) auth()->id());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('vasaccounting.vouchers.show', $voucherModel->id)
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('vasaccounting.vouchers.show', $voucherModel->id)
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }

        return redirect()
            ->route('vasaccounting.vouchers.show', $reversal->id)
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.manual_voucher_reversed')]);
    }

    public function destroy(Request $request, int $voucher): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.vouchers.manage');

        $voucherModel = VasVoucher::query()
            ->where('business_id', $this->businessId($request))
            ->findOrFail($voucher);

        try {
            $this->postingService->deleteDraftVoucher($voucherModel);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('vasaccounting.vouchers.show', $voucherModel->id)
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('vasaccounting.vouchers.show', $voucherModel->id)
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }

        return redirect()
            ->route('vasaccounting.vouchers.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.manual_voucher_deleted')]);
    }
}
