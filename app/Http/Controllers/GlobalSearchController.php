<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalSearchRequest;
use App\Utils\GlobalSearchUtil;
use Illuminate\Http\JsonResponse;

class GlobalSearchController extends Controller
{
    protected GlobalSearchUtil $globalSearchUtil;

    public function __construct(GlobalSearchUtil $globalSearchUtil)
    {
        $this->globalSearchUtil = $globalSearchUtil;
    }

    public function contacts(GlobalSearchRequest $request): JsonResponse
    {
        return $this->respondWithResults($request, function (int $businessId) use ($request) {
            return $this->globalSearchUtil->searchContacts(
                $request->user(),
                $businessId,
                (string) $request->input('q', ''),
                (string) $request->input('type', 'both')
            );
        });
    }

    public function products(GlobalSearchRequest $request): JsonResponse
    {
        return $this->respondWithResults($request, function (int $businessId) use ($request) {
            return $this->globalSearchUtil->searchProducts(
                $request->user(),
                $businessId,
                (string) $request->input('q', '')
            );
        });
    }

    public function salesOrders(GlobalSearchRequest $request): JsonResponse
    {
        return $this->respondWithResults($request, function (int $businessId) use ($request) {
            return $this->globalSearchUtil->searchSalesOrders(
                $request->user(),
                $businessId,
                (string) $request->input('q', '')
            );
        });
    }

    public function purchases(GlobalSearchRequest $request): JsonResponse
    {
        return $this->respondWithResults($request, function (int $businessId) use ($request) {
            return $this->globalSearchUtil->searchPurchases(
                $request->user(),
                $businessId,
                (string) $request->input('q', '')
            );
        });
    }

    protected function respondWithResults(GlobalSearchRequest $request, callable $callback): JsonResponse
    {
        $businessId = (int) $request->session()->get('user.business_id');

        if ($businessId <= 0 || ! $request->user()) {
            return response()->json(['results' => []]);
        }

        return response()->json([
            'results' => $callback($businessId),
        ]);
    }
}
