<?php

namespace Modules\Cms\Http\Requests;

class UpdateBlogV2PostRequest extends StoreBlogV2PostRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('cms.blog.posts.update');
    }
}
