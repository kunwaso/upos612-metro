<?php

namespace Modules\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleBlogPostPublishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('cms.blog.posts.publish');
    }

    public function rules(): array
    {
        return [];
    }
}
