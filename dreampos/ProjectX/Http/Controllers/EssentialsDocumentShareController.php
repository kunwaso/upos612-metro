<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Modules\Essentials\Entities\Document;
use Modules\Essentials\Entities\DocumentShare;
use Modules\Essentials\Notifications\DocumentShareNotification;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsDocumentShareUpdateRequest;
use Modules\ProjectX\Utils\ProjectXEssentialsUtil;

class EssentialsDocumentShareController extends Controller
{
    protected ProjectXEssentialsUtil $projectXEssentialsUtil;

    public function __construct(ProjectXEssentialsUtil $projectXEssentialsUtil)
    {
        $this->projectXEssentialsUtil = $projectXEssentialsUtil;
    }

    public function edit(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        $type = (string) $request->input('type', 'document');
        $document = Document::where('business_id', $business_id)->findOrFail($id);

        if ((int) $document->user_id !== $this->projectXEssentialsUtil->authUserId()) {
            abort(403, __('messages.unauthorized_action'));
        }

        $users = User::forDropdown($business_id, false);
        $roles = app(\App\Utils\ModuleUtil::class)->getDropdownForRoles($business_id);

        $shared_documents = DocumentShare::where('document_id', $id)
            ->get()
            ->groupBy('value_type');

        $shared_role = ! empty($shared_documents['role'])
            ? $shared_documents['role']->pluck('value')->toArray()
            : [];
        $shared_user = ! empty($shared_documents['user'])
            ? $shared_documents['user']->pluck('value')->toArray()
            : [];

        return view('projectx::essentials.document_share.edit', compact('users', 'id', 'roles', 'shared_user', 'shared_role', 'type'));
    }

    public function update(EssentialsDocumentShareUpdateRequest $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        $document = Document::where('business_id', $business_id)->findOrFail($id);
        if ((int) $document->user_id !== $this->projectXEssentialsUtil->authUserId()) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $users = (array) $request->input('user', []);
            $roles = (array) $request->input('role', []);

            $existing_user_id = [0];
            $existing_role_id = [0];

            foreach ($users as $user_id) {
                $user_id = (int) $user_id;
                $existing_user_id[] = $user_id;

                $share = [
                    'document_id' => $document->id,
                    'value_type' => 'user',
                    'value' => $user_id,
                ];

                $doc_share = DocumentShare::updateOrCreate($share);
                if ($doc_share->wasRecentlyCreated) {
                    $shared_user = User::where('business_id', $business_id)->find($user_id);
                    if (! empty($shared_user)) {
                        $shared_user->notify(
                            new DocumentShareNotification(
                                $document,
                                auth()->user(),
                                route('projectx.essentials.documents.index', ['type' => $document->type])
                            )
                        );
                    }
                }
            }

            DocumentShare::where('document_id', $document->id)
                ->where('value_type', 'user')
                ->whereNotIn('value', $existing_user_id)
                ->delete();

            foreach ($roles as $role_id) {
                $role_id = (int) $role_id;
                $existing_role_id[] = $role_id;

                DocumentShare::updateOrCreate([
                    'document_id' => $document->id,
                    'value_type' => 'role',
                    'value' => $role_id,
                ]);
            }

            DocumentShare::where('document_id', $document->id)
                ->where('value_type', 'role')
                ->whereNotIn('value', $existing_role_id)
                ->delete();

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
}
