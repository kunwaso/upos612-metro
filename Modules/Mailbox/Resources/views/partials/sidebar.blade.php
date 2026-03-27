@php
    $folder = $filters['folder'] ?? 'inbox';
    $accountId = $filters['account_id'] ?? null;
@endphp

<div class="d-none d-lg-flex flex-column flex-lg-row-auto w-100 w-lg-275px">
    <div class="card card-flush mb-0">
        <div class="card-body">
            @can('mailbox.send')
                <a href="{{ route('mailbox.compose.create', array_filter(['account_id' => $accountId])) }}" class="btn btn-primary fw-bold w-100 mb-8">
                    {{ __('mailbox::lang.compose') }}
                </a>
            @endcan

            <div class="menu menu-column menu-rounded menu-state-bg menu-state-title-primary menu-state-icon-primary menu-state-bullet-primary mb-10">
                @foreach([
                    'inbox' => ['label' => __('mailbox::lang.inbox'), 'icon' => 'ki-sms', 'count' => $counts['inbox'] ?? 0],
                    'starred' => ['label' => __('mailbox::lang.starred'), 'icon' => 'ki-star', 'count' => $counts['starred'] ?? 0],
                    'sent' => ['label' => __('mailbox::lang.sent'), 'icon' => 'ki-send', 'count' => $counts['sent'] ?? 0],
                    'trash' => ['label' => __('mailbox::lang.trash'), 'icon' => 'ki-trash', 'count' => $counts['trash'] ?? 0],
                ] as $folderKey => $folderItem)
                    <div class="menu-item mb-3">
                        <a
                            href="{{ route('mailbox.index', array_filter(array_merge($filters ?? [], ['folder' => $folderKey]))) }}"
                            class="menu-link {{ $folder === $folderKey ? 'active' : '' }}"
                        >
                            <span class="menu-icon">
                                <i class="ki-duotone {{ $folderItem['icon'] }} fs-2 me-3">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                                </i>
                            </span>
                            <span class="menu-title fw-bold">{{ $folderItem['label'] }}</span>
                            <span class="badge badge-light-primary">{{ $folderItem['count'] }}</span>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="separator separator-dashed my-6"></div>

            <div>
                <div class="fw-bold text-gray-900 fs-6 mb-4">{{ __('mailbox::lang.accounts') }}</div>
                @forelse($accounts as $account)
                    <a
                        href="{{ route('mailbox.index', array_filter(array_merge($filters ?? [], ['account_id' => $account->id]))) }}"
                        class="d-flex align-items-center justify-content-between rounded px-4 py-3 mb-2 mailbox-account-chip {{ (int) $accountId === (int) $account->id ? 'active' : '' }}"
                    >
                        <div>
                            <div class="fw-bold text-gray-900 fs-7">{{ $account->display_name ?: $account->email_address }}</div>
                            <div class="text-muted fs-8">{{ $account->provider_label }}</div>
                        </div>
                        <span class="badge {{ $account->is_active ? 'badge-light-success' : 'badge-light-secondary' }}">
                            {{ $account->is_active ? 'On' : 'Off' }}
                        </span>
                    </a>
                @empty
                    <div class="text-muted fs-7">{{ __('mailbox::lang.no_accounts') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
