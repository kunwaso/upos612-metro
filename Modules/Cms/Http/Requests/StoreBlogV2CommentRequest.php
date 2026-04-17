<?php

namespace Modules\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogV2CommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'comment' => 'required|string|max:3000',
            'parent_id' => 'nullable|integer|exists:cms_blog_comments,id',
        ];
    }
}
