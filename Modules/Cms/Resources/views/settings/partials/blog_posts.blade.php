<div class="pos-tab-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">@lang('cms::lang.blog_posts')</h4>
        @can('cms.blog.posts.create')
            <button type="button" class="btn btn-primary" onclick="document.getElementById('new-blog-form').classList.toggle('hide');">
                @lang('cms::lang.add_blog_post')
            </button>
        @endcan
    </div>

    @can('cms.blog.posts.create')
        <div id="new-blog-form" class="hide mb-4">
            @includeIf('cms::settings.partials.blog_post_form', ['form_action' => route('cms.site-details.blog-posts.store'), 'method' => 'post', 'post' => null, 'meta' => []])
        </div>
    @endcan

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>@lang('cms::lang.title')</th>
                    <th>@lang('cms::lang.category')</th>
                    <th>@lang('cms::lang.priority')</th>
                    <th>@lang('cms::lang.is_enabled')</th>
                    <th>@lang('lang_v1.actions')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($blogPosts ?? [] as $post)
                    <tr>
                        <td>{{ $post->title }}</td>
                        <td>{{ $post->tags }}</td>
                        <td>{{ $post->priority }}</td>
                        <td>
                            @if($post->is_enabled)
                                <span class="label label-success">Published</span>
                            @else
                                <span class="label label-default">Draft</span>
                            @endif
                        </td>
                        <td class="d-flex" style="gap: 8px;">
                            @can('cms.blog.posts.update')
                                <a href="{{ route('cms.site-details.blog-posts.edit', $post->id) }}" class="btn btn-xs btn-info">
                                    <i class="fa fa-edit"></i>
                                </a>
                            @endcan
                            @can('cms.blog.posts.publish')
                                {!! Form::open(['url' => route('cms.site-details.blog-posts.toggle-publish', $post->id), 'method' => 'post']) !!}
                                    <button type="submit" class="btn btn-xs btn-warning">
                                        <i class="fa fa-power-off"></i>
                                    </button>
                                {!! Form::close() !!}
                            @endcan
                            @can('cms.blog.posts.delete')
                                {!! Form::open(['url' => route('cms.site-details.blog-posts.destroy', $post->id), 'method' => 'delete', 'onsubmit' => "return confirm('Are you sure?');"]) !!}
                                    <button type="submit" class="btn btn-xs btn-danger">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                {!! Form::close() !!}
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">@lang('cms::lang.not_found_please_add_one')</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
