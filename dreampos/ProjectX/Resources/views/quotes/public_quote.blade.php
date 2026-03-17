<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('projectx::lang.public_quote') }} - {{ $quoteDisplay['quoteNumber'] ?? ($quote->quote_number ?: $quote->uuid) }}</title>
    <link href="{{ asset('modules/projectx/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('modules/projectx/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<body class="bg-light">
    <div class="container py-10">
        @if(session('status'))
            <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} mb-5">
                {{ session('status.msg') }}
            </div>
        @endif

        <div class="card card-flush">
            <div class="card-header pt-8 d-flex justify-content-between flex-wrap">
                <div>
                    <h2 class="fw-bolder text-gray-900 mb-1">{{ $quoteDisplay['publicQuoteBusinessName'] ?? (optional($quote->business)->name ?: config('app.name')) }}</h2>
                    <div class="text-muted fs-7">{{ __('projectx::lang.public_quote') }}</div>
                </div>
                <div class="text-end">
                    <div class="fw-bold text-gray-900">{{ __('projectx::lang.quote_no') }}: {{ $quoteDisplay['quoteNumber'] ?? ($quote->quote_number ?: $quote->uuid) }}</div>
                    <div class="text-muted fs-7">{{ __('projectx::lang.quote_date') }}: {{ $quoteDisplay['quoteDateDisplay'] ?? '-' }}</div>
                    <div class="text-muted fs-7">{{ __('projectx::lang.valid_until') }}: {{ $quoteDisplay['validUntilDisplay'] ?? '-' }}</div>
                    <button class="btn btn-light-primary btn-sm mt-3" onclick="window.print()">{{ __('projectx::lang.print') }}</button>
                </div>
            </div>
            <div class="card-body pt-6">
                <div class="row g-5 mb-8">
                    <div class="col-md-6">
                        <div class="text-muted fs-7">{{ __('projectx::lang.customer') }}</div>
                        <div class="fw-bold text-gray-900">{{ $quoteDisplay['customerName'] ?? '-' }}</div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="text-muted fs-7">{{ __('projectx::lang.location') }}</div>
                        <div class="fw-bold text-gray-900">{{ $quoteDisplay['locationName'] ?? '-' }}</div>
                    </div>
                </div>

                <div class="row g-5 mb-8">
                    <div class="col-md-12">
                        <div class="border border-dashed border-gray-300 rounded p-5">
                            <div class="fw-bold text-gray-900 mb-2">{{ __('projectx::lang.business_information') }}</div>
                            <div class="text-gray-700 mb-1">{{ $quoteDisplay['locationAddressDisplay'] ?? '-' }}</div>
                            @if(!empty($quote->business->tax_label_1) || !empty($quote->business->tax_number_1))
                                <div class="text-gray-700 mb-1">{{ $quote->business->tax_label_1 ?: __('projectx::lang.tax') }}: {{ $quote->business->tax_number_1 ?: '-' }}</div>
                            @endif
                            @if(!empty($quote->business->tax_label_2) || !empty($quote->business->tax_number_2))
                                <div class="text-gray-700 mb-1">{{ $quote->business->tax_label_2 ?: __('projectx::lang.tax') }}: {{ $quote->business->tax_number_2 ?: '-' }}</div>
                            @endif
                            <div class="text-gray-700 mb-1">{{ __('projectx::lang.phone') }}: {{ optional($quote->location)->mobile ?: (optional($quote->location)->alternate_number ?: '-') }}</div>
                            <div class="text-gray-700">{{ __('projectx::lang.email') }}: {{ optional($quote->location)->email ?: '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold text-muted text-uppercase fs-7">
                                <th>{{ __('projectx::lang.quote_line_item') }}</th>
                                <th>{{ __('projectx::lang.quantity') }}</th>
                                <th>{{ __('projectx::lang.purchase_uom') }}</th>
                                <th>{{ __('projectx::lang.unit_cost') }}</th>
                                <th class="text-end">{{ __('projectx::lang.total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(($quoteDisplay['quoteDisplayLines'] ?? []) as $line)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-gray-900">{{ $line['itemName'] ?? '-' }}</div>
                                        @if(!empty($line['itemCode']))
                                            <div class="text-muted fs-7">{{ $line['itemCodeLabel'] ?? __('projectx::lang.sku') }}: {{ $line['itemCode'] }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $line['quantityPublicDisplay'] ?? '0' }}</td>
                                    <td>{{ $line['purchaseUom'] ?? '-' }}</td>
                                    <td>{{ $line['unitCostPublicDisplay'] ?? '0' }}</td>
                                    <td class="text-end fw-bold">{{ $line['totalCostPublicDisplay'] ?? '0' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-top border-gray-300">
                                <td colspan="4" class="text-end fw-bolder text-gray-900 fs-4">{{ __('projectx::lang.grand_total') }}</td>
                                <td class="text-end fw-bolder text-gray-900 fs-4">{{ $quoteDisplay['quoteGrandTotalPublicDisplay'] ?? '0' }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-muted fs-7 mt-8">
                    {{ $quoteDisplay['publicQuoteFooterNote'] ?? '' }}
                </div>

                @if(!empty($quoteDisplay['remarkDisplay']))
                <div class="card card-flush mt-8">
                    <div class="card-header pt-6">
                        <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.remark') }}</h3>
                    </div>
                    <div class="card-body pt-5">
                        <div class="text-gray-700 fs-7">
                            {!! nl2br(e($quoteDisplay['remarkDisplay'])) !!}
                        </div>
                    </div>
                </div>
                @endif

                <div class="separator separator-dashed my-8"></div>

                @if($quote->confirmed_at)
                    <div class="alert alert-success mb-0">
                        <div class="fw-bold">{{ __('projectx::lang.quote_confirmed_success') }}</div>
                        <div class="fs-7">{{ __('projectx::lang.confirmed_at') }}: {{ $quoteDisplay['publicQuoteConfirmedAtDisplay'] ?? '-' }}</div>
                    </div>
                    @if($quote->confirmation_signature)
                        <div class="mt-5">
                            <div class="text-muted fs-7 mb-2">{{ __('projectx::lang.signature') }}</div>
                            <img src="{{ $quote->confirmation_signature }}" alt="signature" style="max-width: 280px; border: 1px solid #d1d5db; border-radius: 6px;">
                        </div>
                    @endif
                @else
                    <div class="card card-flush mt-5">
                        <div class="card-header">
                            <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.confirm_quote') }}</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('projectx.quotes.public.confirm', ['publicToken' => $quote->public_token]) }}" id="quote_confirm_form">
                                @csrf
                                <input type="hidden" name="signature" id="signature_input">

                                <div class="mb-3">
                                    <label class="form-label">{{ __('projectx::lang.signature') }}</label>
                                    <canvas id="signature_canvas" width="900" height="250" style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; background-color: #fff;"></canvas>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" id="signature_clear_btn" class="btn btn-light">{{ __('projectx::lang.clear_signature') }}</button>
                                    <button type="submit" class="btn btn-primary">{{ __('projectx::lang.confirm_quote') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('quote_confirm_form');
            if (!form) {
                return;
            }

            const canvas = document.getElementById('signature_canvas');
            const input = document.getElementById('signature_input');
            const clearBtn = document.getElementById('signature_clear_btn');
            const ctx = canvas.getContext('2d');
            let drawing = false;
            let hasSignature = false;

            ctx.lineWidth = 2;
            ctx.lineJoin = 'round';
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#111827';

            const getPos = (event) => {
                const rect = canvas.getBoundingClientRect();
                const touch = event.touches ? event.touches[0] : null;
                const clientX = touch ? touch.clientX : event.clientX;
                const clientY = touch ? touch.clientY : event.clientY;
                return {
                    x: clientX - rect.left,
                    y: clientY - rect.top
                };
            };

            const start = (event) => {
                drawing = true;
                const pos = getPos(event);
                ctx.beginPath();
                ctx.moveTo(pos.x, pos.y);
                event.preventDefault();
            };

            const move = (event) => {
                if (!drawing) {
                    return;
                }
                const pos = getPos(event);
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
                hasSignature = true;
                event.preventDefault();
            };

            const end = (event) => {
                drawing = false;
                ctx.closePath();
                event.preventDefault();
            };

            canvas.addEventListener('mousedown', start);
            canvas.addEventListener('mousemove', move);
            canvas.addEventListener('mouseup', end);
            canvas.addEventListener('mouseleave', end);
            canvas.addEventListener('touchstart', start, { passive: false });
            canvas.addEventListener('touchmove', move, { passive: false });
            canvas.addEventListener('touchend', end, { passive: false });

            clearBtn.addEventListener('click', function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasSignature = false;
                input.value = '';
            });

            form.addEventListener('submit', function (event) {
                if (!hasSignature) {
                    event.preventDefault();
                    alert('{{ __('projectx::lang.quote_signature_required') }}');
                    return;
                }

                input.value = canvas.toDataURL('image/png');
            });
        })();
    </script>
</body>
</html>
