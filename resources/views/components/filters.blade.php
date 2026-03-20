<div class="card card-flush mb-5">
    <div class="card-header cursor-pointer" data-bs-toggle="collapse" data-bs-target="#collapseFilter" aria-expanded="true">
        <h3 class="card-title">
            <i class="ki-duotone ki-filter fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
            {{ $title ?? __('report.filters') }}
        </h3>
        <div class="card-toolbar">
            <button type="button" class="btn btn-sm btn-light-primary">
                <i class="ki-duotone ki-down fs-5"><span class="path1"></span></i>
            </button>
        </div>
    </div>
    <div class="collapse show" id="collapseFilter">
        <div class="card-body pt-2 pb-4">
            <div class="row g-3">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
