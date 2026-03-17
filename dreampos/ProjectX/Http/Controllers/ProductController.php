<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProjectX\Entities\Fabric;
use Modules\ProjectX\Utils\FabricManagerUtil;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    protected $moduleUtil;

    protected $fabricUtil;

    public function __construct(ModuleUtil $moduleUtil, FabricManagerUtil $fabricUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->fabricUtil = $fabricUtil;
    }

    public function index(Request $request)
    {
        if (! auth()->user()->can('product.view')) {
            abort(403, __('projectx::lang.unauthorized_action'));
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $fabrics = Fabric::query()
                ->forBusiness($business_id)
                ->with(['compositionItems.catalogComponent:id,label'])
                ->leftJoin('contacts as supplier', function ($join) {
                    $join->on('projectx_fabrics.supplier_contact_id', '=', 'supplier.id')
                        ->on('projectx_fabrics.business_id', '=', 'supplier.business_id');
                })
                ->select([
                    'projectx_fabrics.id',
                    'projectx_fabrics.name as fabric_name',
                    'projectx_fabrics.fabric_sku',
                    'projectx_fabrics.status',
                    'projectx_fabrics.image_path',
                    'projectx_fabrics.purchase_price',
                    'projectx_fabrics.sale_price',
                    'projectx_fabrics.weight_gsm',
                    'supplier.name as supplier_name',
                ])
                ->selectRaw('(SELECT GROUP_CONCAT(pantone_code ORDER BY sort_order, id) FROM projectx_fabric_pantone_items WHERE fabric_id = projectx_fabrics.id) AS pantone_tcx');

            $pantoneCatalog = $this->fabricUtil->getPantoneTcxCatalog();

            return DataTables::of($fabrics)
                ->addColumn('action', function ($row) {
                    return '<a href="' . route('projectx.fabric_manager.fabric', ['fabric_id' => $row->id]) . '" class="btn btn-sm btn-light-primary"><i class="ki-duotone ki-eye fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i> ' . __('projectx::lang.view_fabric') . '</a>';
                })
                ->editColumn('status', function ($row) {
                    $status = (string) ($row->status ?? '');
                    $badgeMap = [
                        Fabric::STATUS_ACTIVE => 'badge-light-primary',
                        Fabric::STATUS_DRAFT => 'badge-light-warning',
                        Fabric::STATUS_NEEDS_APPROVAL => 'badge-light-info',
                        Fabric::STATUS_REJECTED => 'badge-light-danger',
                    ];
                    $badgeClass = $badgeMap[$status] ?? 'badge-light-secondary';
                    $label = $status === '' ? '--' : ucwords(str_replace('_', ' ', $status));

                    return '<span class="badge ' . $badgeClass . '">' . e($label) . '</span>';
                })
                ->editColumn('composition_summary', function ($row) {
                    $summary = $this->fabricUtil->getComponentSummaryForFabric($row);

                    return $summary !== '-' ? e($summary) : '--';
                })
                ->editColumn('pantone_tcx', function ($row) use ($pantoneCatalog) {
                    $v = trim((string) ($row->pantone_tcx ?? ''));
                    if ($v === '') {
                        return '--';
                    }
                    $codes = array_filter(array_map('trim', explode(',', $v)));
                    $badges = [];
                    foreach ($codes as $code) {
                        $info = $pantoneCatalog[$code] ?? null;
                        $hex = $info['hex'] ?? '#000000';
                        $hex = is_string($hex) ? $hex : '#000000';
                        $name = isset($info['name']) ? $info['name'] . ' (' . $code . ')' : $code;
                    $badges[] = '<span class="d-inline-block rounded-circle me-1" style="background-color:' . e($hex) . ';width:1.25rem;height:1.25rem;border:1px solid rgba(0,0,0,0.15);" title="' . e($name) . '" data-bs-toggle="tooltip"></span>';
                    }

                    return '<span class="d-flex align-items-center flex-wrap">' . implode('', $badges) . '</span>';
                })
                ->editColumn('weight_gsm', function ($row) {
                    $v = $row->weight_gsm;
                    if ($v === null || $v === '') {
                        return '--';
                    }

                    return e(number_format((float) $v, 2));
                })
                ->editColumn('supplier_name', function ($row) {
                    $supplierName = trim((string) ($row->supplier_name ?? ''));

                    return $supplierName !== '' ? e($supplierName) : '--';
                })
                ->editColumn('purchase_price', function ($row) {
                    $currency = session('currency');
                    $symbol = ! empty($currency['symbol']) ? $currency['symbol'] : '$';

                    return $symbol . ' ' . number_format((float) ($row->purchase_price ?? 0), 2);
                })
                ->editColumn('sale_price', function ($row) {
                    $currency = session('currency');
                    $symbol = ! empty($currency['symbol']) ? $currency['symbol'] : '$';

                    return $symbol . ' ' . number_format((float) ($row->sale_price ?? 0), 2);
                })
                ->editColumn('fabric_name', function ($row) {
                    $image = ! empty($row->image_path)
                        ? asset('storage/' . ltrim((string) $row->image_path, '/'))
                        : asset('/img/default.png');
                    $fabricSku = trim((string) ($row->fabric_sku ?? ''));
                    $skuLine = $fabricSku !== '' ? '<span class="text-muted fs-7">' . e($fabricSku) . '</span>' : '';

                    return '<div class="d-flex align-items-center">'
                        . '<div class="symbol symbol-40px me-3"><img src="' . $image . '" alt="" class="rounded" /></div>'
                        . '<div class="d-flex flex-column"><span class="fw-bold text-gray-800">' . e($row->fabric_name) . '</span>'
                        . $skuLine . '</div>'
                        . '</div>';
                })
                ->rawColumns(['action', 'status', 'fabric_name', 'pantone_tcx'])
                ->make(true);
        }

        return view('projectx::products.index');
    }
}
