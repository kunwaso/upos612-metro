<?php

namespace Modules\ProjectX\Http\Requests\SiteManager;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingsRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.site_manager.edit');
    }

    public function rules()
    {
        return [
            'site_name' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['nullable', 'string', 'max:500'],
            'hero_subtitle' => ['nullable', 'string', 'max:1000'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'footer_copyright' => ['nullable', 'string', 'max:500'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'nav_items' => ['nullable', 'array'],
            'nav_items.*.label' => ['nullable', 'string', 'max:100'],
            'nav_items.*.url' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation()
    {
        $nav = $this->input('nav_items');
        if (is_string($nav)) {
            $decoded = json_decode($nav, true);
            $this->merge(['nav_items' => is_array($decoded) ? $decoded : []]);
        }
    }
}
