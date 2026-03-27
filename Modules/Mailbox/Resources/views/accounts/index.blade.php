@extends('layouts.app')

@section('title', __('mailbox::lang.accounts'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ __('mailbox::lang.accounts') }}
                </h1>
                <div class="text-muted fw-semibold fs-7">Connect Gmail or your custom domain mailbox</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('mailbox.index') }}" class="btn btn-sm btn-light">{{ __('mailbox::lang.inbox') }}</a>
                <a href="{{ route('mailbox.accounts.oauth.google.redirect') }}" class="btn btn-sm btn-primary">{{ __('mailbox::lang.connect_gmail') }}</a>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            @include('mailbox::partials.status')

            <div class="row g-6">
                <div class="col-xl-5">
                    <div class="card card-flush h-100">
                        <div class="card-header pt-7">
                            <h3 class="card-title fw-bold text-gray-900">{{ __('mailbox::lang.custom_domain_account') }}</h3>
                        </div>
                        <div class="card-body pt-5">
                            <form method="POST" action="{{ route('mailbox.accounts.store') }}" data-mailbox-test-form="true" data-mailbox-test-url="{{ route('mailbox.accounts.test') }}">
                                @csrf
                                <div class="row g-5">
                                    <div class="col-md-6">
                                        <label class="form-label">Display name</label>
                                        <input type="text" name="display_name" class="form-control form-control-solid" placeholder="Sales mailbox">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Sender name</label>
                                        <input type="text" name="sender_name" class="form-control form-control-solid" placeholder="Sales Team">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Email address</label>
                                        <input type="email" name="email_address" class="form-control form-control-solid" placeholder="email@customdomain.com" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">IMAP host</label>
                                        <input type="text" name="imap_host" class="form-control form-control-solid" placeholder="imap.customdomain.com" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Port</label>
                                        <input type="number" name="imap_port" class="form-control form-control-solid" value="993" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Encryption</label>
                                        <select name="imap_encryption" class="form-select form-select-solid">
                                            <option value="ssl">SSL</option>
                                            <option value="tls">TLS</option>
                                            <option value="starttls">STARTTLS</option>
                                            <option value="none">None</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">IMAP username</label>
                                        <input type="text" name="imap_username" class="form-control form-control-solid" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">IMAP password</label>
                                        <input type="password" name="imap_password" class="form-control form-control-solid" required>
                                        <div class="text-muted fs-8 mt-1">Zoho tip: use a Zoho app password, not your normal account password.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Inbox folder</label>
                                        <input type="text" name="imap_inbox_folder" class="form-control form-control-solid" value="INBOX">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Sent folder</label>
                                        <input type="text" name="imap_sent_folder" class="form-control form-control-solid" value="Sent">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Trash folder</label>
                                        <input type="text" name="imap_trash_folder" class="form-control form-control-solid" value="Trash">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SMTP host</label>
                                        <input type="text" name="smtp_host" class="form-control form-control-solid" placeholder="smtp.customdomain.com" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Port</label>
                                        <input type="number" name="smtp_port" class="form-control form-control-solid" value="465" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Encryption</label>
                                        <select name="smtp_encryption" class="form-select form-select-solid">
                                            <option value="ssl">SSL</option>
                                            <option value="tls">TLS</option>
                                            <option value="starttls">STARTTLS</option>
                                            <option value="none">None</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SMTP username</label>
                                        <input type="text" name="smtp_username" class="form-control form-control-solid" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SMTP password</label>
                                        <input type="password" name="smtp_password" class="form-control form-control-solid" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" name="sync_enabled" value="1" checked>
                                            <span class="form-check-label">Enable background sync</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-8">
                                    <div class="text-muted fs-7" data-mailbox-test-feedback></div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-light-primary" data-mailbox-test-button data-loading-text="{{ __('mailbox::lang.testing_connection') }}">{{ __('mailbox::lang.test_connection') }}</button>
                                        <button type="submit" class="btn btn-primary">Save account</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-xl-7">
                    <div class="card card-flush h-100">
                        <div class="card-header pt-7">
                            <h3 class="card-title fw-bold text-gray-900">Connected mailboxes</h3>
                        </div>
                        <div class="card-body pt-5">
                            @forelse($accounts as $account)
                                <div class="border border-gray-200 rounded p-5 mb-5">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
                                        <div>
                                            <div class="fw-bold text-gray-900">{{ $account->display_name ?: $account->email_address }}</div>
                                            <div class="text-muted fs-7">{{ $account->email_address }} &middot; {{ $account->provider_label }}</div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge {{ $account->is_active ? 'badge-light-success' : 'badge-light-secondary' }}">
                                                {{ $account->is_active ? 'Active' : 'Disconnected' }}
                                            </span>
                                            @if($account->last_synced_at)
                                                <span class="badge badge-light-info">{{ __('mailbox::lang.last_sync') }} {{ $account->last_synced_at->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($account->provider === 'imap' && $account->is_active)
                                        <form method="POST" action="{{ route('mailbox.accounts.update', ['account' => $account->id]) }}" class="mt-5" data-mailbox-test-form="true" data-mailbox-test-url="{{ route('mailbox.accounts.test') }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="row g-4">
                                                <div class="col-md-6">
                                                    <label class="form-label">Display name</label>
                                                    <input type="text" name="display_name" class="form-control form-control-solid" value="{{ $account->display_name }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Sender name</label>
                                                    <input type="text" name="sender_name" class="form-control form-control-solid" value="{{ $account->sender_name }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">IMAP host</label>
                                                    <input type="text" name="imap_host" class="form-control form-control-solid" value="{{ $account->imap_host }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Port</label>
                                                    <input type="number" name="imap_port" class="form-control form-control-solid" value="{{ $account->imap_port }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Encryption</label>
                                                    <select name="imap_encryption" class="form-select form-select-solid">
                                                        @foreach(['ssl', 'tls', 'starttls', 'none'] as $encryption)
                                                            <option value="{{ $encryption }}" {{ ($account->imap_encryption ?: 'none') === $encryption ? 'selected' : '' }}>{{ strtoupper($encryption) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">IMAP username</label>
                                                    <input type="text" name="imap_username" class="form-control form-control-solid" value="{{ $account->imap_username }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">IMAP password</label>
                                                    <input type="password" name="imap_password" class="form-control form-control-solid" placeholder="Leave blank to keep existing">
                                                    <div class="text-muted fs-8 mt-1">Zoho tip: keep or replace with a Zoho app password.</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Inbox folder</label>
                                                    <input type="text" name="imap_inbox_folder" class="form-control form-control-solid" value="{{ $account->imap_inbox_folder }}">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Sent folder</label>
                                                    <input type="text" name="imap_sent_folder" class="form-control form-control-solid" value="{{ $account->imap_sent_folder }}">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Trash folder</label>
                                                    <input type="text" name="imap_trash_folder" class="form-control form-control-solid" value="{{ $account->imap_trash_folder }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">SMTP host</label>
                                                    <input type="text" name="smtp_host" class="form-control form-control-solid" value="{{ $account->smtp_host }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Port</label>
                                                    <input type="number" name="smtp_port" class="form-control form-control-solid" value="{{ $account->smtp_port }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Encryption</label>
                                                    <select name="smtp_encryption" class="form-select form-select-solid">
                                                        @foreach(['ssl', 'tls', 'starttls', 'none'] as $encryption)
                                                            <option value="{{ $encryption }}" {{ ($account->smtp_encryption ?: 'none') === $encryption ? 'selected' : '' }}>{{ strtoupper($encryption) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">SMTP username</label>
                                                    <input type="text" name="smtp_username" class="form-control form-control-solid" value="{{ $account->smtp_username }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">SMTP password</label>
                                                    <input type="password" name="smtp_password" class="form-control form-control-solid" placeholder="Leave blank to keep existing">
                                                </div>
                                                <div class="col-md-12">
                                                    <input type="hidden" name="existing_account_id" value="{{ $account->id }}">
                                                    <input type="hidden" name="email_address" value="{{ $account->email_address }}">
                                                    <label class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="checkbox" name="sync_enabled" value="1" {{ $account->sync_enabled ? 'checked' : '' }}>
                                                        <span class="form-check-label">Enable background sync</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between flex-wrap gap-3 mt-5">
                                                <div class="d-flex flex-column gap-2">
                                                    <div class="text-muted fs-7" data-mailbox-test-feedback></div>
                                                    <div class="d-flex gap-2">
                                                        <button type="button" class="btn btn-light-primary" data-mailbox-test-button data-loading-text="{{ __('mailbox::lang.testing_connection') }}">{{ __('mailbox::lang.test_connection') }}</button>
                                                        <button type="submit" class="btn btn-light-primary">Save changes</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    @endif

                                    <div class="d-flex justify-content-between align-items-center mt-5 flex-wrap gap-3">
                                        <div class="text-muted fs-7">
                                            @if($account->last_sync_error_message)
                                                {{ $account->last_sync_error_message }}
                                            @elseif($account->provider === 'gmail')
                                                Gmail accounts use Google OAuth and sync in the background after connection.
                                            @else
                                                IMAP and SMTP credentials are stored encrypted for this user only.
                                            @endif
                                        </div>
                                        <div class="d-flex gap-2">
                                            @if($account->is_active)
                                                <form method="POST" action="{{ route('mailbox.accounts.sync', ['account' => $account->id]) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-light">Sync now</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('mailbox.accounts.destroy', ['account' => $account->id]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-light-danger">{{ __('mailbox::lang.disconnect') }}</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted fs-6">{{ __('mailbox::lang.no_accounts') }}</div>
                            @endforelse
                        </div>
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

