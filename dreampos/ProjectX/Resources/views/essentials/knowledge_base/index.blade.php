@extends('projectx::layouts.main')

@section('title', __('essentials::lang.knowledge_base'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.knowledge_base')</h1>
    </div>
    <a href="{{ route('projectx.essentials.knowledge-base.create') }}" class="btn btn-primary btn-sm">@lang('essentials::lang.add_knowledge_base')</a>
</div>

<div class="card card-flush">
    <div class="card-body pt-7">
        <div class="accordion" id="projectx_kb_tree">
            @forelse($knowledge_bases as $kb)
                <div class="accordion-item mb-4 border border-gray-300 rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#projectx_kb_{{ $kb->id }}">
                            {{ $kb->title }}
                        </button>
                    </h2>
                    <div id="projectx_kb_{{ $kb->id }}" class="accordion-collapse collapse" data-bs-parent="#projectx_kb_tree">
                        <div class="accordion-body">
                            <div class="d-flex flex-wrap gap-2 mb-4">
                                <a href="{{ route('projectx.essentials.knowledge-base.show', ['knowledge_base' => $kb->id]) }}" class="btn btn-light-primary btn-sm">@lang('messages.view')</a>
                                @if(auth()->user()->can('essentials.edit_knowledge_base'))
                                    <a href="{{ route('projectx.essentials.knowledge-base.edit', ['knowledge_base' => $kb->id]) }}" class="btn btn-light-primary btn-sm">@lang('messages.edit')</a>
                                @endif
                                @if(auth()->user()->can('essentials.delete_knowledge_base'))
                                    <button type="button" class="btn btn-light-danger btn-sm projectx-kb-delete" data-id="{{ $kb->id }}">@lang('messages.delete')</button>
                                @endif
                                <a href="{{ route('projectx.essentials.knowledge-base.create', ['parent' => $kb->id]) }}" class="btn btn-light-primary btn-sm">@lang('essentials::lang.add_section')</a>
                            </div>

                            @foreach($kb->children as $section)
                                <div class="border border-gray-200 rounded p-4 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-semibold">{{ $section->title }}</div>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('projectx.essentials.knowledge-base.show', ['knowledge_base' => $section->id]) }}" class="btn btn-light btn-sm">@lang('messages.view')</a>
                                            @if(auth()->user()->can('essentials.edit_knowledge_base'))
                                                <a href="{{ route('projectx.essentials.knowledge-base.edit', ['knowledge_base' => $section->id]) }}" class="btn btn-light btn-sm">@lang('messages.edit')</a>
                                            @endif
                                            <a href="{{ route('projectx.essentials.knowledge-base.create', ['parent' => $section->id]) }}" class="btn btn-light-primary btn-sm">@lang('essentials::lang.add_article')</a>
                                        </div>
                                    </div>
                                    @if($section->children->isNotEmpty())
                                        <ul class="mt-3">
                                            @foreach($section->children as $article)
                                                <li class="mb-2">
                                                    <a href="{{ route('projectx.essentials.knowledge-base.show', ['knowledge_base' => $article->id]) }}">{{ $article->title }}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-muted">@lang('essentials::lang.no_data_found')</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
(function () {
    $(document).on('click', '.projectx-kb-delete', function () {
        var id = $(this).data('id');
        if (!confirm(@json(__('messages.sure')))) {
            return;
        }

        $.ajax({
            method: 'DELETE',
            url: @json(route('projectx.essentials.knowledge-base.destroy', ['knowledge_base' => '__ID__'])).replace('__ID__', id),
            data: {_token: @json(csrf_token())},
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    window.location.reload();
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });
})();
</script>
@endsection
