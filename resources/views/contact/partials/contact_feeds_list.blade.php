@if ($feeds->isEmpty())
    <div class="card card-flush">
        <div class="card-body text-center py-12">
            <div class="text-muted fw-semibold fs-6">
                No feed records found for this provider yet.
            </div>
        </div>
    </div>
@else
    @foreach ($feeds as $feed)
        @php
            $display_date = !empty($feed->published_at) ? $feed->published_at : $feed->fetched_at;
            $domain = parse_url($feed->canonical_url, PHP_URL_HOST);
        @endphp
        <div class="card card-flush mb-7">
            <div class="card-header pt-7">
                <div class="d-flex align-items-start w-100">
                    <div class="symbol symbol-45px me-4">
                        <span class="symbol-label bg-light-primary">
                            <i class="ki-duotone ki-message-text-2 fs-2 text-primary">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <a href="{{ $feed->canonical_url }}" target="_blank" rel="noopener noreferrer"
                            class="text-gray-900 text-hover-primary fs-6 fw-bold">
                            {{ $feed->title }}
                        </a>
                        <div class="text-gray-500 fw-semibold fs-7 mt-1 d-flex flex-wrap gap-3">
                            <span>{{ $feed->source_name ?: $domain }}</span>
                            <span>{{ !empty($display_date) ? $display_date->format('d M Y H:i') : '-' }}</span>
                            <span class="badge badge-light-info text-uppercase">{{ $provider }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-3 pb-6">
                @if (!empty($feed->snippet))
                    <div class="fs-6 fw-normal text-gray-700 mb-4">
                        {{ $feed->snippet }}
                    </div>
                @endif
                <a href="{{ $feed->canonical_url }}" target="_blank" rel="noopener noreferrer"
                    class="btn btn-sm btn-light-primary">
                    Open Source
                </a>
            </div>
        </div>
    @endforeach
@endif
