<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Essentials\Entities\Document;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsDocumentStoreRequest;
use Modules\ProjectX\Utils\ProjectXEssentialsUtil;
use Yajra\DataTables\Facades\DataTables;

class EssentialsDocumentController extends Controller
{
    protected ProjectXEssentialsUtil $projectXEssentialsUtil;

    public function __construct(ProjectXEssentialsUtil $projectXEssentialsUtil)
    {
        $this->projectXEssentialsUtil = $projectXEssentialsUtil;
    }

    public function index(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        $type = (string) $request->input('type', 'document');
        if (! in_array($type, ['document', 'memos'])) {
            $type = 'document';
        }

        $auth_id = $this->projectXEssentialsUtil->authUserId();
        $role_id = $this->projectXEssentialsUtil->getUserRoleId($auth_id);

        if ($request->ajax()) {
            $documents = Document::query()
                ->leftJoin('essentials_document_shares as shares', 'essentials_documents.id', '=', 'shares.document_id')
                ->join('users', 'essentials_documents.user_id', '=', 'users.id')
                ->where('essentials_documents.business_id', $business_id)
                ->where('essentials_documents.type', $type)
                ->where(function ($query) use ($auth_id, $role_id) {
                    $query->where('essentials_documents.user_id', $auth_id)
                        ->orWhere(function ($q) use ($auth_id) {
                            $q->where('shares.value_type', 'user')
                                ->where('shares.value', $auth_id);
                        });

                    if (! empty($role_id)) {
                        $query->orWhere(function ($q) use ($role_id) {
                            $q->where('shares.value_type', 'role')
                                ->where('shares.value', $role_id);
                        });
                    }
                })
                ->select([
                    'essentials_documents.id',
                    'essentials_documents.name',
                    'essentials_documents.description',
                    'essentials_documents.type',
                    'essentials_documents.user_id',
                    'essentials_documents.created_at',
                    'users.first_name',
                    'users.last_name',
                ])
                ->groupBy('essentials_documents.id');

            return DataTables::of($documents)
                ->addColumn('owner', function ($row) {
                    return trim((string) ($row->first_name . ' ' . $row->last_name));
                })
                ->editColumn('name', function ($row) {
                    if ($row->type === 'document') {
                        $file = explode('_', (string) $row->name, 2);

                        return $file[1] ?? $row->name;
                    }

                    return $row->name;
                })
                ->addColumn('action', function ($row) use ($auth_id, $type) {
                    $buttons = [];

                    if ((int) $row->user_id === $auth_id) {
                        $buttons[] = '<a href="' . route('projectx.essentials.document-share.edit', ['id' => $row->id, 'type' => $type]) . '" class="dropdown-item">' . e(__('essentials::lang.share')) . '</a>';
                        $buttons[] = '<a href="#" class="dropdown-item projectx-delete-document" data-id="' . e((string) $row->id) . '">' . e(__('messages.delete')) . '</a>';
                    }

                    if ($type === 'memos') {
                        $buttons[] = '<a href="' . route('projectx.essentials.documents.show', ['document' => $row->id]) . '" class="dropdown-item">' . e(__('messages.view')) . '</a>';
                    } else {
                        $buttons[] = '<a href="' . route('projectx.essentials.documents.download', ['id' => $row->id]) . '" class="dropdown-item">' . e(__('essentials::lang.download')) . '</a>';
                    }

                    return '<div class="dropdown">'
                        . '<button type="button" class="btn btn-sm btn-light btn-active-light-primary" data-bs-toggle="dropdown">' . e(__('messages.actions')) . '</button>'
                        . '<div class="dropdown-menu dropdown-menu-end">' . implode('', $buttons) . '</div>'
                        . '</div>';
                })
                ->editColumn('created_at', function ($row) {
                    return ! empty($row->created_at) ? Carbon::parse((string) $row->created_at)->format('Y-m-d H:i') : '';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('projectx::essentials.document.index', ['type' => $type]);
    }

    public function store(EssentialsDocumentStoreRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $user_id = $this->projectXEssentialsUtil->authUserId();

        try {
            $type = (string) $request->input('type');
            if ($type === 'document') {
                $name = app(\App\Utils\ModuleUtil::class)->uploadFile($request, 'name', 'documents');
            } else {
                $name = (string) $request->input('name');
            }

            Document::create([
                'business_id' => $business_id,
                'user_id' => $user_id,
                'type' => $type,
                'name' => $name,
                'description' => (string) $request->input('description', ''),
            ]);

            return redirect()
                ->route('projectx.essentials.documents.index', ['type' => $type === 'memos' ? 'memos' : 'document'])
                ->with('status', ['success' => true, 'msg' => __('lang_v1.success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return redirect()->back()->withInput()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function show(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        $memo = Document::where('business_id', $business_id)
            ->where('type', 'memos')
            ->findOrFail($id);

        $user_id = $this->projectXEssentialsUtil->authUserId();
        $role_id = $this->projectXEssentialsUtil->getUserRoleId($user_id);
        $is_owner = (int) $memo->user_id === $user_id;
        $is_shared = $this->projectXEssentialsUtil->isDocumentSharedWithUser($memo->id, $user_id, $role_id);
        if (! $is_owner && ! $is_shared) {
            abort(403, __('messages.unauthorized_action'));
        }

        return view('projectx::essentials.document.show', compact('memo'));
    }

    public function destroy(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        try {
            $document = Document::where('business_id', $business_id)->findOrFail($id);
            if ((int) $document->user_id !== $this->projectXEssentialsUtil->authUserId()) {
                return $this->respondUnauthorized(__('messages.unauthorized_action'));
            }

            if ($document->type === 'document') {
                Storage::delete('documents/' . $document->name);
            }

            $document->delete();

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.success'));
            }

            return redirect()
                ->route('projectx.essentials.documents.index', ['type' => $document->type])
                ->with('status', ['success' => true, 'msg' => __('lang_v1.success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function download(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        $user_id = $this->projectXEssentialsUtil->authUserId();
        $role_id = $this->projectXEssentialsUtil->getUserRoleId($user_id);

        $document = Document::where('business_id', $business_id)
            ->where('type', 'document')
            ->findOrFail($id);

        $is_owner = (int) $document->user_id === $user_id;
        $is_shared = $this->projectXEssentialsUtil->isDocumentSharedWithUser($document->id, $user_id, $role_id);

        if (! $is_owner && ! $is_shared) {
            abort(403, __('messages.unauthorized_action'));
        }

        $file_parts = explode('_', (string) $document->name, 2);
        $download_name = $file_parts[1] ?? $document->name;

        return Storage::download('documents/' . $document->name, $download_name);
    }
}
