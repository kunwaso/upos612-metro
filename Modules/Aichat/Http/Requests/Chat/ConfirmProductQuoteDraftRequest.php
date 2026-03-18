<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Aichat\Entities\ProductQuoteDraft;

class ConfirmProductQuoteDraftRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.quote_wizard.use');
    }

    public function rules()
    {
        return [
            'draft_id' => ['required', 'uuid'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $business_id = (int) $this->session()->get('user.business_id');
            $user_id = (int) auth()->id();
            $draftId = (string) $this->input('draft_id');
            $conversationId = (string) $this->route('id');

            $draft = ProductQuoteDraft::forBusiness($business_id)
                ->forUser($user_id)
                ->forConversation($conversationId)
                ->where('id', $draftId)
                ->first();

            if (! $draft) {
                $validator->errors()->add('draft_id', __('aichat::lang.quote_assistant_draft_not_found'));

                return;
            }

            if ($draft->isExpired()) {
                $validator->errors()->add('draft_id', __('aichat::lang.quote_assistant_draft_not_found'));

                return;
            }

            if ($draft->status !== ProductQuoteDraft::STATUS_READY) {
                $validator->errors()->add('draft_id', __('aichat::lang.quote_assistant_draft_not_ready'));
            }
        });
    }
}
