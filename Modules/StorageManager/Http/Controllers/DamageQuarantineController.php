<?php

namespace Modules\StorageManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Http\Requests\ReportDamageRequest;
use Modules\StorageManager\Http\Requests\ResolveDamageRequest;
use Modules\StorageManager\Services\DamageQuarantineService;
use Modules\StorageManager\Utils\StorageManagerUtil;

class DamageQuarantineController extends Controller
{
    public function __construct(
        protected DamageQuarantineService $damageQuarantineService,
        protected StorageManagerUtil $storageManagerUtil
    ) {
    }

    public function index()
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.operate')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $locations = $this->storageManagerUtil->getLocationsDropdown($businessId);
        $locationId = (int) request('location_id', 0);
        $board = $this->damageQuarantineService->boardForLocation($businessId, $locationId ?: null);

        return view('storagemanager::damage.index', [
            'locations' => $locations,
            'locationId' => $locationId,
            'boardSummary' => $board['boardSummary'],
            'documentRows' => $board['documentRows'],
            'approvalRows' => $board['approvalRows'],
            'quarantineSlotOptions' => $board['quarantineSlotOptions'],
            'availableBuckets' => $board['availableBuckets'],
        ]);
    }

    public function show(int $document)
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.operate') && ! auth()->user()->can('storage_manager.approve')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $context = $this->damageQuarantineService->getWorkbench($businessId, $document);

        return view('storagemanager::damage.show', $context);
    }

    public function store(ReportDamageRequest $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        try {
            $document = $this->damageQuarantineService->reportDamage($businessId, $request->validated(), $userId);

            return redirect()
                ->route('storage-manager.damage.show', $document->id)
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.damage_reported_successfully'),
                ]);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with('status', [
                    'success' => false,
                    'msg' => $exception->getMessage(),
                ]);
        }
    }

    public function resolve(ResolveDamageRequest $request, int $document)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $documentModel = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'damage')
            ->findOrFail($document);

        try {
            $resolvedDocument = $this->damageQuarantineService->resolveDocument($businessId, $documentModel, $request->validated(), $userId);

            return redirect()
                ->route('storage-manager.damage.show', $resolvedDocument->id)
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.damage_resolution_saved_successfully'),
                ]);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with('status', [
                    'success' => false,
                    'msg' => $exception->getMessage(),
                ]);
        }
    }
}
