@if ($feeds->isEmpty())
    <div class="card card-flush">
        <div class="card-body text-center py-12">
            <div class="text-muted fw-semibold fs-6">
                No Google news stories have been saved for this contact yet.
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
            <div class="card-body py-7">
                <div class="d-flex flex-column flex-lg-row gap-6">
                    <div class="flex-shrink-0">
                        @if (!empty($feed->image_url))
                            <a href="{{ $feed->canonical_url }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ $feed->image_url }}" alt="{{ $feed->title }}"
                                    class="rounded w-200px h-125px object-fit-cover">
                            </a>
                        @else
                            <div class="symbol symbol-125px">
                                <span class="symbol-label bg-light-primary">
                                    <i class="ki-duotone ki-message-text-2 fs-1 text-primary">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    </i>
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="flex-grow-1 d-flex flex-column">
                        <a href="{{ $feed->canonical_url }}" target="_blank" rel="noopener noreferrer"
                            class="text-gray-900 text-hover-primary fs-4 fw-bold mb-2">
                            {{ $feed->title }}
                        </a>
                        <div class="text-gray-500 fw-semibold fs-7 mt-1 d-flex flex-wrap gap-3">
                            <span>{{ $feed->source_name ?: $domain }}</span>
                            <span>{{ !empty($display_date) ? $display_date->format('d M Y H:i') : '-' }}</span>
                            <span class="badge badge-light-info text-uppercase">Google</span>
                        </div>
                        @if (!empty($feed->snippet))
                            <div class="fs-6 fw-normal text-gray-700 mt-4 mb-5">
                                {{ $feed->snippet }}
                            </div>
                        @endif
                        <div class="mt-auto">
                            <a href="{{ $feed->canonical_url }}" target="_blank" rel="noopener noreferrer"
                                class="btn btn-sm btn-light-primary">
                                Open Article
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
