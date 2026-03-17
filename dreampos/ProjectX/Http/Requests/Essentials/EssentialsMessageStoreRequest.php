<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsMessageStoreRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess() && $this->hasAnyPermission(['essentials.create_message']);
    }

    public function rules()
    {
        $business_id = $this->businessId();

        return [
            'message' => ['required', 'string'],
            'location_id' => [
                'nullable',
                'integer',
                Rule::exists('business_locations', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
        ];
    }
}
