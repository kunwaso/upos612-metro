# Mailbox

Admin-only mailbox module for UPOS staff users.

## What It Does

`Mailbox` lets each signed-in staff user connect either:

- a Gmail account through Google OAuth
- a custom-domain mailbox through IMAP for receive and SMTP for send

The module stores a tenant-safe local mirror of mailbox messages so the inbox UI stays fast and can render inside the Metronic inbox screens under `public/html/apps/inbox`.

V1 scope:

- per-user ownership only
- Gmail OAuth only for hosted providers
- IMAP + SMTP manual settings for custom-domain mailboxes
- folders: Inbox, Sent, Starred, Trash
- background sync via queue jobs every 5 minutes by default
- attachments downloaded lazily into private storage

## Required Env Vars

- `MAILBOX_GMAIL_CLIENT_ID`
- `MAILBOX_GMAIL_CLIENT_SECRET`
- `MAILBOX_GMAIL_REDIRECT_URI`
- `MAILBOX_SYNC_ENABLED`
- `MAILBOX_SYNC_INTERVAL_MINUTES`
- `MAILBOX_SYNC_BATCH_SIZE`
- `MAILBOX_INITIAL_BACKFILL_COUNT`
- `MAILBOX_ATTACHMENT_DISK`

Suggested defaults:

```env
MAILBOX_GMAIL_CLIENT_ID=
MAILBOX_GMAIL_CLIENT_SECRET=
MAILBOX_GMAIL_REDIRECT_URI=https://your-domain.test/mailbox/accounts/oauth/google/callback
MAILBOX_SYNC_ENABLED=true
MAILBOX_SYNC_INTERVAL_MINUTES=5
MAILBOX_SYNC_BATCH_SIZE=50
MAILBOX_INITIAL_BACKFILL_COUNT=50
MAILBOX_ATTACHMENT_DISK=local
QUEUE_CONNECTION=database
```

## Google OAuth Setup

1. Create a Google Cloud project.
2. Enable the Gmail API.
3. Create an OAuth web application credential.
4. Add the callback URL from `MAILBOX_GMAIL_REDIRECT_URI`.
5. Store the client id and secret in the env file.

The Gmail callback route used by this module is:

```text
/mailbox/accounts/oauth/google/callback
```

The module requests these scopes:

- `openid`
- `email`
- `profile`
- `https://www.googleapis.com/auth/gmail.modify`
- `https://www.googleapis.com/auth/gmail.send`

## Install And Update

First make sure dependencies are installed:

```bash
composer install
```

Then install or update the module:

```bash
composer dump-autoload
php artisan module:migrate Mailbox
php artisan migrate --path=database/migrations/2026_03_27_000001_create_jobs_table.php
php artisan migrate --path=database/migrations/2026_03_27_000002_create_failed_jobs_table.php
php artisan vendor:publish --tag=mailbox-assets --force
php artisan permission:cache-reset
```

If your deployment flow uses the module installer UI, the module install controller also runs the mailbox migrations, queue-table migrations, and permission bootstrap for you.

## Queue And Scheduler

Mailbox sync and send flows are queue-backed.

Required runtime processes:

```bash
php artisan queue:work --queue=mailbox-sync,mailbox-send,default
php artisan schedule:work
```

If you use cron instead of `schedule:work`, run Laravel scheduler every minute:

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## Asset Publish

Publish mailbox assets after deployment when mailbox CSS or JS changes:

```bash
php artisan vendor:publish --tag=mailbox-assets --force
```

Published assets land in:

```text
public/modules/mailbox
```

## Permissions

The module adds these permissions:

- `mailbox.view`
- `mailbox.manage_accounts`
- `mailbox.send`

The install flow grants them to roles matching `Admin#*`.

## Operational Notes

- Gmail accounts rely on refresh tokens. If Google stops returning a refresh token, disconnect the app in Google and reconnect with consent.
- IMAP and SMTP secrets are encrypted at rest on the `mailbox_accounts` table.
- Attachments are stored on demand under the configured disk, not mirrored during sync.
- This module expects tenant-safe use through `business_id` and `user_id` ownership checks.
- Shared team mailboxes, Outlook OAuth, push sync, drafts, archive, and spam are outside v1.

## Verification Commands

Use these after changes:

```bash
composer dump-autoload
php artisan test --filter=Mailbox
php artisan route:list | findstr mailbox
```

If `route:list` fails, check for unrelated application boot errors first, then rerun.
