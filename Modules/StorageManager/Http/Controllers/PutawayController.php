<?php

namespace Modules\StorageManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Http\Requests\CompletePutawayRequest;
use Modules\StorageManager\Services\PutawayService;
use Modules\StorageManager\Utils\StorageManagerUtil;

class PutawayController extends Controller
{
    public function __construct(
        protected PutawayService $putawayService,
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
        $queue = $this->putawayService->queueForLocation($businessId, $locationId ?: null);

        return view('storagemanager::putaway.index', [
            'locations' => $locations,
            'locationId' => $locationId,
            'queueSummary' => $queue['queueSummary'],
            'documents' => $queue['documents'],
        ]);
    }

    public function show(int $document)
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.operate')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $context = $this->putawayService->getWorkbench($businessId, $document);

        return view('storagemanager::putaway.show', $context);
    }

    public function complete(CompletePutawayRequest $request, int $document)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $documentModel = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'putaway')
            ->findOrFail($document);

        try {
            $this->putawayService->completePutaway(
                $businessId,
                $documentModel,
                $request->validated(),
                $userId
            );

            return redirect()
                ->route('storage-manager.putaway.show', $document)
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.putaway_completed_successfully'),
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

    public function reopen(Request $request, int $document)
    {
        if (! auth()->user()->can('storage_manager.operate')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $documentModel = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'putaway')
            ->findOrFail($document);

        try {
            $this->putawayService->reopenPutaway(
                $businessId,
                $documentModel,
                $userId
            );

            return redirect()
                ->route('storage-manager.putaway.show', $document)
                ->with('status', [
                    'success' => true,
                    'msg' => 'Putaway reopened successfully.',
                ]);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->with('status', [
                    'success' => false,
                    'msg' => $exception->getMessage(),
                ]);
        }
    }
}
