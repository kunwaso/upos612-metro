<?php

return [
    'name' => 'Mailbox',
    'module_version' => '1.0.0',
    'attachment_disk' => env('MAILBOX_ATTACHMENT_DISK', env('FILESYSTEM_DISK', env('FILESYSTEM_DRIVER', 'local'))),
    'folders' => [
        'inbox' => 'Inbox',
        'sent' => 'Sent',
        'starred' => 'Starred',
        'trash' => 'Trash',
    ],
    'imap' => [
        'default_inbox_folder' => env('MAILBOX_IMAP_INBOX_FOLDER', 'INBOX'),
        'default_sent_folder' => env('MAILBOX_IMAP_SENT_FOLDER', 'Sent'),
        'default_trash_folder' => env('MAILBOX_IMAP_TRASH_FOLDER', 'Trash'),
        'timeout' => env('MAILBOX_IMAP_TIMEOUT', 30),
        'validate_cert' => env('MAILBOX_IMAP_VALIDATE_CERT', true),
    ],
    'sync' => [
        'enabled' => env('MAILBOX_SYNC_ENABLED', true),
        'interval_minutes' => env('MAILBOX_SYNC_INTERVAL_MINUTES', 5),
        'batch_size' => env('MAILBOX_SYNC_BATCH_SIZE', 50),
        'initial_backfill_count' => env('MAILBOX_INITIAL_BACKFILL_COUNT', 50),
    ],
    'gmail' => [
        'scopes' => [
            'openid',
            'email',
            'profile',
            'https://www.googleapis.com/auth/gmail.modify',
            'https://www.googleapis.com/auth/gmail.send',
        ],
    ],
];
