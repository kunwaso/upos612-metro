<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 {{ $toolbarWrapperClass }}">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-column gap-4 gap-lg-5">
        <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center flex-lg-stack gap-4 gap-lg-0">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-lg-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ $title }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    @foreach($breadcrumbs as $index => $crumb)
                        @if($index > 0)
                            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                        @endif
                        <li class="breadcrumb-item {{ empty($crumb['url']) ? 'text-muted' : 'text-muted' }}">
                            @if(!empty($crumb['url']))
                                <a href="{{ $crumb['url'] }}" class="text-muted text-hover-primary">{{ $crumb['label'] }}</a>
                            @else
                                {{ $crumb['label'] }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
            @isset($contextActions)
                @if($contextActions->isNotEmpty())
                    <div class="d-flex align-items-center flex-nowrap overflow-x-auto gap-2 ms-lg-auto pb-1">
                        {{ $contextActions }}
                    </div>
                @endif
            @endisset
        </div>
        @if($showMainNav && !empty($storageManagerToolbarMainNav))
            <nav class="w-100" aria-label="{{ __('lang_v1.storage_manager') }}">
                <div class="overflow-x-auto pb-1">
                    <div class="d-flex flex-nowrap align-items-center gap-2">
                        @foreach($storageManagerToolbarMainNav as $navItem)
                            <a href="{{ $navItem['href'] }}"
                               class="{{ $navItem['cssClass'] }} text-nowrap"
                               @if(! empty($navItem['isActive'])) aria-current="page" @endif>
                                @if(!empty($navItem['icon_html']))
                                    {!! $navItem['icon_html'] !!}
                                @endif
                                {{ $navItem['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </nav>
        @endif
    </div>
</div>
