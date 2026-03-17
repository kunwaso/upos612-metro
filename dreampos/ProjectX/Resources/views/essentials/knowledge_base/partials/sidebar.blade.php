<div class="menu menu-column menu-rounded menu-sub-indention fw-semibold">
    <div class="menu-item">
        <a href="{{ route('projectx.essentials.knowledge-base.show', ['knowledge_base' => $knowledge_base->id]) }}" class="menu-link {{ (int) $knowledge_base->id === (int) ($kb_object->id ?? 0) ? 'active' : '' }}">
            <span class="menu-title">{{ $knowledge_base->title }}</span>
        </a>
    </div>
    @foreach($knowledge_base->children as $section)
        <div class="menu-item menu-accordion {{ (int) $section->id === (int) $section_id ? 'show' : '' }}">
            <span class="menu-link">
                <span class="menu-title">{{ $section->title }}</span>
                <span class="menu-arrow"></span>
            </span>
            <div class="menu-sub menu-sub-accordion">
                <div class="menu-item">
                    <a href="{{ route('projectx.essentials.knowledge-base.show', ['knowledge_base' => $section->id]) }}" class="menu-link {{ (int) $section->id === (int) $section_id && empty($article_id) ? 'active' : '' }}">
                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                        <span class="menu-title">@lang('messages.view')</span>
                    </a>
                </div>
                @foreach($section->children as $article)
                    <div class="menu-item">
                        <a href="{{ route('projectx.essentials.knowledge-base.show', ['knowledge_base' => $article->id]) }}" class="menu-link {{ (int) $article->id === (int) $article_id ? 'active' : '' }}">
                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                            <span class="menu-title">{{ $article->title }}</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
