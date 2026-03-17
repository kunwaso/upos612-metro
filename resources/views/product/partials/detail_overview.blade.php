{{-- Product detail: Overview tab – all fields from view-modal --}}
<div class="col-12">
    <div class="card card-flush">
        <div class="card-header">
            <h3 class="card-title fw-bold text-gray-900">@lang('product.overview')</h3>
        </div>
        <div class="card-body pt-0">
            <div class="row g-5 g-xl-10">
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('product.sku')</div>
                        <div class="fs-5 fw-bold text-gray-800">{{ $product->sku ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('product.brand')</div>
                        <div class="fs-5 fw-bold text-gray-800">{{ $product->brand->name ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('product.unit')</div>
                        <div class="fs-5 fw-bold text-gray-800">{{ $product->unit->short_name ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('product.barcode_type')</div>
                        <div class="fs-5 fw-bold text-gray-800">{{ $product->barcode_type ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('product.category')</div>
                        <div class="fs-5 fw-bold text-gray-800">{{ $product->category->name ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('product.sub_category')</div>
                        <div class="fs-5 fw-bold text-gray-800">{{ $product->sub_category->name ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('product.product_type')</div>
                        <div class="fs-5 fw-bold text-gray-800">@lang('lang_v1.' . $product->type)</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('product.manage_stock')</div>
                        <div class="fs-5 fw-bold text-gray-800">@lang($product->enable_stock ? 'messages.yes' : 'messages.no')</div>
                    </div>
                </div>
                @if($product->enable_stock)
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('product.alert_quantity')</div>
                        <div class="fs-5 fw-bold text-gray-800">{{ $product->alert_quantity ?? '—' }}</div>
                    </div>
                </div>
                @endif
                @if(!empty($product->warranty))
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('lang_v1.warranty')</div>
                        <div class="fs-5 fw-bold text-gray-800">{{ $product->warranty->display_name ?? '—' }}</div>
                    </div>
                </div>
                @endif
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('product.expires_in')</div>
                        <div class="fs-5 fw-bold text-gray-800">
                            @if(!empty($product->expiry_period) && !empty($product->expiry_period_type))
                                {{ $product->expiry_period }} @lang($product->expiry_period_type == 'months' ? 'product.months' : 'product.days')
                            @else
                                @lang('product.not_applicable')
                            @endif
                        </div>
                    </div>
                </div>
                @if($product->weight)
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('lang_v1.weight')</div>
                        <div class="fs-5 fw-bold text-gray-800">{{ $product->weight }}</div>
                    </div>
                </div>
                @endif
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('product.applicable_tax')</div>
                        <div class="fs-5 fw-bold text-gray-800">{{ $product->product_tax->name ?? __('lang_v1.none') }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('product.selling_price_tax_type')</div>
                        <div class="fs-5 fw-bold text-gray-800">@lang('product.' . $product->tax_type)</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('lang_v1.available_in_locations')</div>
                    <div class="fs-6 text-gray-800">
                        @if($product->product_locations && $product->product_locations->count() > 0)
                            {{ $product->product_locations->pluck('name')->join(', ') }}
                        @else
                            @lang('lang_v1.none')
                        @endif
                    </div>
                </div>
                @if(!empty($product->media) && $product->media->isNotEmpty())
                <div class="col-12">
                    <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('lang_v1.product_brochure')</div>
                    <div class="fs-6">
                        @foreach($product->media as $media)
                            <a href="{{ $media->display_url }}" download="{{ $media->display_name }}" class="btn btn-sm btn-light-primary me-2">
                                <i class="ki-duotone ki-file-down fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                                {{ $media->display_name }}
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif
                @foreach($productCustomFields ?? [] as $customField)
                <div class="col-md-6 col-lg-4">
                    <div class="border border-gray-300 border-dashed rounded p-5">
                        <div class="fw-semibold fs-7 text-gray-500 mb-2">{{ $customField['label'] }}</div>
                        <div class="fs-5 fw-bold text-gray-800">{{ $customField['value'] }}</div>
                    </div>
                </div>
                @endforeach
                @if(!empty($product->product_description))
                <div class="col-12">
                    <div class="fw-semibold fs-7 text-gray-500 mb-2">@lang('lang_v1.description')</div>
                    <div class="fs-6 text-gray-800">{!! $product->product_description !!}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
