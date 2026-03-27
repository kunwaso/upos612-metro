@extends('layouts.app')

@section('title', __('mailbox::lang.inbox'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack flex-wrap gap-4">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ __('mailbox::lang.mailbox') }}
                </h1>
                <div class="text-muted fw-semibold fs-7">{{ __('mailbox::lang.inbox') }}</div>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                @can('mailbox.manage_accounts')
                    <a href="{{ route('mailbox.accounts.index') }}" class="btn btn-sm btn-light">
                        {{ __('mailbox::lang.accounts') }}
                    </a>
                @endcan
                @can('mailbox.send')
                    <a href="{{ route('mailbox.compose.create') }}" class="btn btn-sm btn-primary">
                        {{ __('mailbox::lang.compose') }}
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            @include('mailbox::partials.status')

            <div class="d-flex flex-column flex-lg-row">
                @include('mailbox::partials.sidebar', ['accounts' => $accounts, 'counts' => $counts, 'filters' => $filters])

                <div class="flex-lg-row-fluid ms-lg-7 ms-xl-10">
                    <div class="card">
                        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                            <form method="GET" action="{{ route('mailbox.index') }}" class="d-flex flex-wrap gap-3 w-100 align-items-center">
                                <input type="hidden" name="folder" value="{{ $filters['folder'] }}">
                                <div class="w-250px">
                                    <select name="account_id" class="form-select form-select-solid" onchange="this.form.submit()">
                                        <option value="">All accounts</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ (int) ($filters['account_id'] ?? 0) === (int) $account->id ? 'selected' : '' }}>
                                                {{ $account->display_name ?: $account->email_address }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-grow-1">
                                    <input type="text" name="search" class="form-control form-control-solid" placeholder="Search mailbox" value="{{ $filters['search'] }}">
                                </div>
                                <div class="w-175px">
                                    <select name="status" class="form-select form-select-solid">
                                        <option value="">All states</option>
                                        <option value="unread" {{ ($filters['status'] ?? '') === 'unread' ? 'selected' : '' }}>Unread</option>
                                        <option value="read" {{ ($filters['status'] ?? '') === 'read' ? 'selected' : '' }}>Read</option>
                                        <option value="starred" {{ ($filters['status'] ?? '') === 'starred' ? 'selected' : '' }}>Starred</option>
                                        <option value="attachments" {{ ($filters['status'] ?? '') === 'attachments' ? 'selected' : '' }}>Has attachments</option>
                                    </select>
                                </div>
                                <div class="w-150px">
                                    <select name="sort" class="form-select form-select-solid">
                                        <option value="newest" {{ ($filters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' }}>Newest</option>
                                        <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-light-primary">Filter</button>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover table-row-dashed fs-6 gy-5 my-0" id="kt_inbox_listing">
                                <thead class="d-none">
                                    <tr>
                                        <th>Flags</th>
                                        <th>Author</th>
                                        <th>Title</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($messages as $item)
                                        @php
                                            $sender = collect($item->from_json)->first();
                                            $senderLabel = $sender['name'] ?? ($sender['email'] ?? 'Unknown');
                                            $initial = strtoupper(substr($senderLabel, 0, 1));
                                            $timestamp = $item->primary_timestamp;
                                        @endphp
                                        <tr class="{{ $item->is_read ? '' : 'mailbox-unread-row' }}">
                                            <td class="min-w-80px ps-9">
                                                <div class="d-flex align-items-center gap-1">
                                                    <form method="POST" action="{{ route('mailbox.messages.star', ['message' => $item->id]) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-icon btn-color-gray-500 btn-active-color-primary w-35px h-35px">
                                                            <i class="ki-duotone ki-star fs-3 {{ $item->is_starred ? 'text-warning' : '' }}"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('mailbox.messages.trash', ['message' => $item->id]) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-icon btn-color-gray-500 btn-active-color-primary w-35px h-35px">
                                                            <i class="ki-duotone ki-trash fs-3"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                            <td class="w-150px w-md-175px">
                                                <a href="{{ route('mailbox.threads.show', ['message' => $item->id]) }}" class="d-flex align-items-center text-gray-900">
                                                    <div class="symbol symbol-35px me-3">
                                                        <div class="symbol-label bg-light-primary">
                                                            <span class="text-primary">{{ $initial }}</span>
                                                        </div>
                                                    </div>
                                                    <span class="fw-semibold">{{ $senderLabel }}</span>
                                                </a>
                                            </td>
                                            <td>
                                                <div class="text-gray-900 gap-1 pt-2">
                                                    <a href="{{ route('mailbox.threads.show', ['message' => $item->id]) }}" class="text-gray-900">
                                                        <span class="fw-bold">{{ $item->subject ?: '(No subject)' }}</span>
                                                        <span class="fw-bold d-none d-md-inline">-</span>
                                                        <span class="d-none d-md-inline text-muted">{{ \Illuminate\Support\Str::limit($item->snippet ?: trim(strip_tags($item->body_text)), 90) }}</span>
                                                    </a>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <div class="badge badge-light-primary">{{ ucfirst($item->folder) }}</div>
                                                    @if($item->has_attachments)
                                                        <div class="badge badge-light-info">Attachment</div>
                                                    @endif
                                                    @if($item->is_starred)
                                                        <div class="badge badge-light-warning">Starred</div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="w-120px text-end fs-7 pe-9">
                                                <span class="fw-semibold {{ $item->is_read ? 'text-muted' : 'text-gray-900' }}">
                                                    {{ $timestamp ? $timestamp->format('M d, H:i') : '' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-20">
                                                <div class="text-gray-500 fw-semibold fs-6">
                                                    {{ $accounts->isEmpty() ? __('mailbox::lang.no_accounts') : 'No messages matched the current filter.' }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-5">
                        {{ $messages->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
    <link href="{{ asset('modules/mailbox/css/mailbox.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('javascript')
    <script src="{{ asset('modules/mailbox/js/mailbox.js') }}"></script>
@endsection
