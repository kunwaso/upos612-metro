<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Modules\Essentials\Entities\KnowledgeBase;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsKnowledgeBaseStoreRequest;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsKnowledgeBaseUpdateRequest;
use Modules\ProjectX\Utils\ProjectXEssentialsUtil;

class EssentialsKnowledgeBaseController extends Controller
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

        $user_id = $this->projectXEssentialsUtil->authUserId();
        $knowledge_bases = $this->projectXEssentialsUtil->knowledgeBaseTree($business_id, $user_id);

        return view('projectx::essentials.knowledge_base.index', compact('knowledge_bases'));
    }

    public function create(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        $parent = null;
        $users = [];

        if ($request->filled('parent')) {
            $parent = KnowledgeBase::where('business_id', $business_id)->findOrFail((int) $request->input('parent'));
        } else {
            $users = User::forDropdown($business_id, false);
        }

        return view('projectx::essentials.knowledge_base.create', compact('parent', 'users'));
    }

    public function store(EssentialsKnowledgeBaseStoreRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $user_id = $this->projectXEssentialsUtil->authUserId();

        try {
            $input = $request->validated();
            $input['business_id'] = $business_id;
            $input['created_by'] = $user_id;
            $input['kb_type'] = (string) ($input['kb_type'] ?? 'knowledge_base');
            $input['parent_id'] = ! empty($input['parent_id']) ? (int) $input['parent_id'] : null;
            $input['share_with'] = $input['share_with'] ?? null;

            $kb = KnowledgeBase::create([
                'business_id' => $input['business_id'],
                'created_by' => $input['created_by'],
                'kb_type' => $input['kb_type'],
                'parent_id' => $input['parent_id'],
                'share_with' => $input['share_with'],
                'title' => (string) $input['title'],
                'content' => ! empty($input['content']) ? (string) $input['content'] : null,
            ]);

            if ($kb->kb_type === 'knowledge_base' && $kb->share_with === 'only_with') {
                $kb->users()->sync((array) $request->input('user_ids', []));
            }

            return redirect()
                ->route('projectx.essentials.knowledge-base.index')
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

        $kb_object = KnowledgeBase::where('business_id', $business_id)
            ->with(['children', 'children.children', 'users'])
            ->findOrFail($id);

        $users = $kb_object->users->pluck('user_full_name')->values()->all();

        $section_id = '';
        $article_id = '';

        if ($kb_object->kb_type === 'knowledge_base') {
            $knowledge_base = $kb_object;
        } elseif ($kb_object->kb_type === 'section') {
            $knowledge_base = KnowledgeBase::where('business_id', $business_id)
                ->with(['children', 'children.children'])
                ->findOrFail((int) $kb_object->parent_id);
            $section_id = $kb_object->id;
        } else {
            $section = KnowledgeBase::where('business_id', $business_id)
                ->findOrFail((int) $kb_object->parent_id);
            $section_id = $section->id;
            $article_id = $kb_object->id;
            $knowledge_base = KnowledgeBase::where('business_id', $business_id)
                ->with(['children', 'children.children'])
                ->findOrFail((int) $section->parent_id);
        }

        return view('projectx::essentials.knowledge_base.show', compact('kb_object', 'knowledge_base', 'section_id', 'article_id', 'users'));
    }

    public function edit(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        if (! auth()->user()->can('essentials.edit_knowledge_base')) {
            abort(403, __('messages.unauthorized_action'));
        }

        $kb = KnowledgeBase::where('business_id', $business_id)
            ->with(['users'])
            ->findOrFail($id);

        $users = $kb->kb_type === 'knowledge_base' ? User::forDropdown($business_id, false) : [];

        return view('projectx::essentials.knowledge_base.edit', compact('kb', 'users'));
    }

    public function update(EssentialsKnowledgeBaseUpdateRequest $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        if (! auth()->user()->can('essentials.edit_knowledge_base')) {
            abort(403, __('messages.unauthorized_action'));
        }

        try {
            $kb = KnowledgeBase::where('business_id', $business_id)->findOrFail($id);

            $kb->update([
                'title' => (string) $request->input('title'),
                'content' => (string) $request->input('content', ''),
                'share_with' => $request->input('share_with'),
            ]);

            if ($kb->kb_type === 'knowledge_base' && $kb->share_with === 'only_with') {
                $kb->users()->sync((array) $request->input('user_ids', []));
            }

            return redirect()
                ->route('projectx.essentials.knowledge-base.index')
                ->with('status', ['success' => true, 'msg' => __('lang_v1.success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return redirect()->back()->withInput()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function destroy(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        if (! auth()->user()->can('essentials.delete_knowledge_base')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            KnowledgeBase::where('business_id', $business_id)
                ->where('id', $id)
                ->delete();

            return $this->respondSuccess(__('lang_v1.success'));
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }
}
