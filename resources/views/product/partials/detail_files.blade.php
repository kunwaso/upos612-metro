@php
    $detailFiles = collect($product->media ?? [])->filter(function ($media) {
        return (string) ($media->model_media_type ?? '') === 'product_detail_file';
    })->values();

    $fileIconMap = [
        'pdf' => 'pdf.svg',
        'doc' => 'doc.svg',
        'docx' => 'doc.svg',
        'xls' => 'xml.svg',
        'xlsx' => 'xml.svg',
        'csv' => 'xml.svg',
        'xml' => 'xml.svg',
        'sql' => 'sql.svg',
        'css' => 'css.svg',
        'ai' => 'ai.svg',
        'tif' => 'tif.svg',
        'tiff' => 'tif.svg',
    ];
@endphp

<div class="col-12">
    <div class="d-flex flex-wrap flex-stack pt-10 pb-8">
        <h3 class="fw-bold my-2">{{ __('product.product_files') }}
            <span class="fs-6 text-gray-500 fw-semibold ms-1">{{ __('product.resources_count', ['count' => $detailFiles->count()]) }}</span>
        </h3>

        <div class="d-flex flex-wrap my-1 align-items-center gap-2">
            <div class="d-flex align-items-center position-relative my-1 me-2">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3"><span class="path1"></span><span class="path2"></span></i>
                <input type="text" class="form-control form-control-sm form-control-solid w-150px ps-10" placeholder="{{ __('product.search') }}..." />
            </div>

            @can('product.update')
                <form method="POST" action="{{ route('product.detail.files.upload', ['id' => $product->id]) }}" enctype="multipart/form-data" class="d-inline">
                    @csrf
                    <input type="file" id="product_detail_file_input" name="detail_file" class="d-none" onchange="this.form.submit();" required>
                    <label for="product_detail_file_input" class="btn btn-primary btn-sm mb-0">{{ __('product.upload_file') }}</label>
                </form>
            @endcan
        </div>
    </div>

    <div class="row g-6 g-xl-9 mb-6 mb-xl-9">
        @forelse($detailFiles as $file)
            @php
                $extension = strtolower(pathinfo((string) $file->display_name, PATHINFO_EXTENSION));
                $icon = $fileIconMap[$extension] ?? 'upload.svg';
            @endphp
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex justify-content-center text-center flex-column p-8">
                        <a href="{{ route('product.detail.files.download', ['id' => $product->id, 'media_id' => $file->id]) }}" class="text-gray-800 text-hover-primary d-flex flex-column">
                            <div class="symbol symbol-60px mb-5">
                                <img src="{{ asset('assets/media/svg/files/' . $icon) }}" alt="" />
                            </div>
                            <div class="fs-5 fw-bold mb-2">{{ $file->display_name }}</div>
                        </a>

                        <div class="fs-7 fw-semibold text-gray-500 mb-3">
                            {{ __('product.created_at') }}: {{ optional($file->created_at)->format('M d, Y h:i A') }}
                        </div>

                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('product.detail.files.download', ['id' => $product->id, 'media_id' => $file->id]) }}" class="btn btn-light-primary btn-sm">
                                {{ __('product.download') }}
                            </a>
                            @can('product.update')
                                <form method="POST" action="{{ route('product.detail.files.delete', ['id' => $product->id, 'media_id' => $file->id]) }}" onsubmit="return confirm('{{ __('product.activity_delete') }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-light-danger btn-sm">{{ __('product.delete') }}</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-body d-flex justify-content-center text-center flex-column p-8">
                        <div class="symbol symbol-60px mb-5">
                            <img src="{{ asset('assets/media/svg/files/upload.svg') }}" alt="" />
                        </div>
                        <div class="fs-5 fw-bold mb-2">{{ __('product.no_records_found') }}</div>
                        <div class="fs-7 fw-semibold text-gray-500">{{ __('product.drag_drop_files') }}</div>
                    </div>
                </div>
            </div>
        @endforelse

        @can('product.update')
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex justify-content-center text-center flex-column p-8">
                        <label for="product_detail_file_input" class="text-gray-800 text-hover-primary d-flex flex-column cursor-pointer">
                            <div class="symbol symbol-60px mb-5">
                                <img src="{{ asset('assets/media/svg/files/upload.svg') }}" alt="" />
                            </div>
                            <div class="fs-5 fw-bold mb-2">{{ __('product.upload_file') }}</div>
                        </label>
                        <div class="fs-7 fw-semibold text-gray-500">{{ __('product.drag_drop_files') }}</div>
                    </div>
                </div>
            </div>
        @endcan
    </div>
</div>
