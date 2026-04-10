<?php

declare(strict_types=1);

return [
    'compatibility' => [
        'markdown_files' => [
            'INDEX.md',
            'core-http-entry.md',
            'core-http-controllers.md',
            'core-utils-index.md',
            'module-*.md',
        ],
        'commands' => [
            'composer entrypoints:generate',
            'composer entrypoints:check',
        ],
    ],
    'core_maps' => [
        'core-http-entry' => [
            'title' => 'Core HTTP Entry',
            'purpose' => 'Use this map when the task is in the root Laravel app rather than a `Modules/*` package.',
            'triggers' => [
                'Core (root)',
                'root routes',
                'app/Http/Controllers',
                'app/Utils',
            ],
            'start_here' => [
                'routes/web.php',
                'routes/api.php',
                'app/Http/Controllers',
            ],
            'workflows' => [
                [
                    'name' => 'Root route to controller trace',
                    'paths' => ['routes/web.php', 'app/Http/Controllers'],
                    'notes' => 'Use this path when a task references a root route name, middleware, or controller action.',
                ],
                [
                    'name' => 'Install/bootstrap route trace',
                    'paths' => ['routes/install_r.php', 'app/Http/Controllers/Install'],
                    'notes' => 'Start here when setup/installer flows are involved.',
                ],
            ],
            'edit_bundles' => [
                [
                    'name' => 'Root web flow bundle',
                    'paths' => ['routes/web.php', 'app/Http/Controllers', 'resources/views'],
                    'notes' => 'Most root feature edits touch route declaration, controller behavior, and a root Blade surface together.',
                ],
            ],
            'tests' => [
                'tests/Feature',
            ],
            'verify_commands' => [
                'php artisan route:list',
                'php artisan test --filter=Feature',
            ],
            'search_keywords' => [
                'routes/web.php',
                'routes/api.php',
                'App\\Http\\Controllers',
                'home',
                'sell',
                'products',
                'contacts',
                'quotes',
                'reports',
            ],
            'related_docs' => [
                'ai/laravel-conventions.md',
                'ai/security-and-auth.md',
                'ai/agent-tools-and-mcp.md',
            ],
        ],
        'core-http-controllers' => [
            'title' => 'Core HTTP Controllers',
            'purpose' => 'Verified controller index for root files in `app/Http/Controllers/*.php` plus `Auth/`, `Install/`, and `Restaurant/`.',
            'triggers' => [
                'root controller edits',
                'Auth controller',
                'Install controller',
                'Restaurant controller',
            ],
            'start_here' => [
                'app/Http/Controllers',
                'app/Http/Controllers/Auth',
                'app/Http/Controllers/Install',
                'app/Http/Controllers/Restaurant',
            ],
            'workflows' => [
                [
                    'name' => 'Controller ownership check',
                    'paths' => ['app/Http/Controllers', 'routes/web.php', 'routes/api.php'],
                    'notes' => 'Confirm owning route first, then open only the target controller.',
                ],
            ],
            'edit_bundles' => [
                [
                    'name' => 'Controller + route bundle',
                    'paths' => ['app/Http/Controllers', 'routes/web.php', 'routes/api.php'],
                    'notes' => 'When action signatures change, update route wiring in the same pass.',
                ],
            ],
            'tests' => [
                'tests/Feature',
            ],
            'verify_commands' => [
                'php artisan test --filter=Feature',
            ],
            'search_keywords' => [
                'Controller.php',
                'Auth',
                'Install',
                'Restaurant',
            ],
            'related_docs' => [
                'ai/laravel-conventions.md',
                'ai/security-and-auth.md',
            ],
        ],
        'core-utils-index' => [
            'title' => 'Core Utils Index',
            'purpose' => 'Primary utility index for `app/Utils/*Util.php` and remaining helper/service files under `app/Utils`.',
            'triggers' => [
                'shared util refactor',
                'App\\Utils dependency',
                'cross-module helper behavior',
            ],
            'start_here' => [
                'app/Utils',
                'app/Http/Controllers',
            ],
            'workflows' => [
                [
                    'name' => 'Util caller impact pass',
                    'paths' => ['app/Utils', 'app/Http/Controllers', 'Modules'],
                    'notes' => 'Before changing shared utils, confirm likely callers across root and modules.',
                ],
            ],
            'edit_bundles' => [
                [
                    'name' => 'Util + caller bundle',
                    'paths' => ['app/Utils', 'app/Http/Controllers', 'Modules/*/Http/Controllers'],
                    'notes' => 'Most util edits require at least one caller update or verification read.',
                ],
            ],
            'tests' => [
                'tests/Unit',
                'tests/Feature',
            ],
            'verify_commands' => [
                'php artisan test --filter=Unit',
            ],
            'search_keywords' => [
                'Util.php',
                'App\\Utils',
            ],
            'related_docs' => [
                'ai/laravel-conventions.md',
                'ai/database-map.md',
            ],
        ],
    ],
    'modules' => [
        'Aichat' => [
            'purpose' => 'Tenant-scoped AI chat entry map for web chat, Telegram ingress, shared conversation links, and quote-wizard flows.',
            'web_summary' => 'admin chat routes under `/aichat/chat/*`, Telegram webhook ingress under `/aichat/telegram/webhook/{webhookKey}`, and signed shared conversations under `/aichat/chat/shared/{conversation}`',
            'api_summary' => 'auth API placeholder route for `/aichat`',
            'index_trigger' => '`Aichat`, chat drawer, Telegram, quote wizard',
            'index_note' => 'Local module folder present; admin chat plus shared chat routes',
            'keywords' => ['aichat', 'chat', 'telegram', 'quote-wizard', 'conversations', 'shared'],
            'use_when' => [
                'Chat drawer open/close or route behavior is broken.',
                'Telegram webhook, shared conversation link, or quote wizard behavior changes.',
            ],
            'start_here' => [
                'Modules/Aichat/Routes/web.php',
                'Modules/Aichat/Http/Controllers/ChatController.php',
                'Modules/Aichat/Resources/views/chat',
            ],
            'workflows' => [
                [
                    'name' => 'Chat drawer issue',
                    'paths' => [
                        'Modules/Aichat/Http/Controllers/ChatController.php',
                        'Modules/Aichat/Resources/views/chat',
                    ],
                    'notes' => 'Start from chat controller + chat Blade to verify initial render and drawer state.',
                ],
                [
                    'name' => 'Quote wizard conversation flow',
                    'paths' => [
                        'Modules/Aichat/Http/Controllers/ChatQuoteWizardController.php',
                        'Modules/Aichat/Routes/web.php',
                    ],
                    'notes' => 'Wizard behavior usually couples route definition with controller actions.',
                ],
                [
                    'name' => 'Telegram ingress',
                    'paths' => [
                        'Modules/Aichat/Http/Controllers/TelegramWebhookController.php',
                        'Modules/Aichat/Routes/web.php',
                    ],
                    'notes' => 'Validate webhook route key and controller handling together.',
                ],
            ],
            'edit_bundles' => [
                [
                    'name' => 'Drawer + settings bundle',
                    'paths' => [
                        'Modules/Aichat/Http/Controllers/ChatController.php',
                        'Modules/Aichat/Http/Controllers/ChatSettingsController.php',
                        'Modules/Aichat/Resources/views/chat',
                    ],
                    'notes' => 'Most drawer UX issues involve both chat behavior and settings flags.',
                ],
                [
                    'name' => 'Webhook bundle',
                    'paths' => [
                        'Modules/Aichat/Routes/web.php',
                        'Modules/Aichat/Http/Controllers/TelegramWebhookController.php',
                    ],
                    'notes' => 'Keep route + handler aligned for webhook key and auth behavior.',
                ],
            ],
            'tests' => [
                'Modules/Aichat/Tests',
            ],
            'verify_commands' => [
                'php artisan test --filter=Aichat',
                'php artisan route:list --name=aichat',
            ],
            'related_docs' => [
                'ai/aichat-authz-baseline.md',
                'Modules/Aichat/README.md',
                'ai/security-and-auth.md',
                'ai/product-copilot-patterns.md',
            ],
        ],
        'Cms' => [
            'purpose' => 'Storefront and CMS-admin entry map for root shopping pages, blogs, public contact pages, RFQ flow, and `/cms/*` admin settings.',
            'web_summary' => 'storefront routes for `/`, `/shop/*`, backward-compatible `/c/*` redirects, and admin routes under `/cms/*`',
            'api_summary' => 'auth API placeholder route for `/cms`',
            'index_trigger' => '`Cms`, storefront, `shop/*`, blog, contact-us, RFQ',
            'index_note' => 'Deepest storefront map in this folder',
            'keywords' => ['cms.home', 'shop/', 'cms-page', 'site-details', 'decor-store', 'contact-us', 'blog', 'rfq', 'catalog', 'collections'],
            'use_when' => [
                'Storefront layout, shop pages, or public blog/contact behavior is changing.',
                'Admin CMS page CRUD, site-details settings, or RFQ form behavior needs investigation.',
            ],
            'start_here' => [
                'Modules/Cms/Routes/web.php',
                'Modules/Cms/Http/Controllers/CmsController.php',
                'Modules/Cms/Http/Controllers/SettingsController.php',
                'Modules/Cms/Resources/views/frontend',
            ],
            'workflows' => [
                [
                    'name' => 'Storefront catalog and RFQ flow',
                    'paths' => [
                        'Modules/Cms/Routes/web.php',
                        'Modules/Cms/Http/Controllers/CmsController.php',
                        'Modules/Cms/Utils/CmsStorefrontCatalogUtil.php',
                        'Modules/Cms/Utils/CmsStorefrontRfqUtil.php',
                        'Modules/Cms/Http/Requests/StoreCmsStorefrontRfqRequest.php',
                        'Modules/Cms/Resources/views/frontend',
                    ],
                    'notes' => 'RFQ bugs span route throttle, form request validation, util persistence, and the frontend views.',
                ],
                [
                    'name' => 'Admin CMS page management',
                    'paths' => [
                        'Modules/Cms/Routes/web.php',
                        'Modules/Cms/Http/Controllers/CmsPageController.php',
                        'Modules/Cms/Resources/views/page',
                    ],
                    'notes' => 'Page CRUD: route resource except show, controller, and admin page views.',
                ],
                [
                    'name' => 'Site details and settings',
                    'paths' => [
                        'Modules/Cms/Http/Controllers/SettingsController.php',
                        'Modules/Cms/Resources/views/settings',
                    ],
                    'notes' => 'Site-details resource routes; admin settings views.',
                ],
            ],
            'edit_bundles' => [
                [
                    'name' => 'Storefront frontend bundle',
                    'paths' => [
                        'Modules/Cms/Routes/web.php',
                        'Modules/Cms/Http/Controllers/CmsController.php',
                        'Modules/Cms/Utils/CmsStorefrontCatalogUtil.php',
                        'Modules/Cms/Resources/views/frontend',
                        'Modules/Cms/Resources/views/layouts',
                    ],
                    'notes' => 'Most public storefront changes touch route, controller, catalog util, and frontend/layout views together.',
                ],
                [
                    'name' => 'Admin page CRUD bundle',
                    'paths' => [
                        'Modules/Cms/Http/Controllers/CmsPageController.php',
                        'Modules/Cms/Resources/views/page',
                    ],
                    'notes' => 'Admin page editor and listing views.',
                ],
            ],
            'tests' => [
                'Modules/Cms/Tests',
            ],
            'verify_commands' => [
                'php artisan test --filter=Cms',
                'php artisan route:list --name=cms',
            ],
            'related_docs' => [
                'ai/ui-components.md',
                'ai/laravel-conventions.md',
                'ai/security-and-auth.md',
            ],
        ],
        'Essentials' => [
            'purpose' => 'Entry map for the Essentials and HRM module surfaces under `/essentials/*` and `/hrm/*`.',
            'web_summary' => 'dashboards, documents, todos, reminders, messaging, knowledge base, transcripts, and HRM routes under `/hrm/*`',
            'api_summary' => 'present but empty placeholder in this checkout',
            'index_trigger' => '`Essentials`, HRM, attendance, payroll, leave, todo, knowledge-base',
            'index_note' => 'Local module folder present; `Routes/api.php` exists but is empty in this checkout',
            'keywords' => ['essentials', 'hrm', 'attendance', 'payroll', 'leave', 'todo', 'knowledge-base', 'transcripts', 'shift', 'holiday', 'reminder', 'document', 'messaging'],
            'use_when' => [
                'Todo, document, reminder, messaging, or knowledge-base behavior under /essentials/* is changing.',
                'HRM flows (attendance, leave, payroll, shifts, holidays, sales targets) under /hrm/* need investigation or fixes.',
                'Transcript preview, translation, or storage behavior is involved.',
            ],
            'start_here' => [
                'Modules/Essentials/Routes/web.php',
                'Modules/Essentials/Http/Controllers/EssentialsController.php',
                'Modules/Essentials/Http/Controllers/ToDoController.php',
                'Modules/Essentials/Resources/views',
            ],
            'workflows' => [
                [
                    'name' => 'Todo and document collaboration',
                    'paths' => [
                        'Modules/Essentials/Http/Controllers/ToDoController.php',
                        'Modules/Essentials/Http/Controllers/DocumentController.php',
                        'Modules/Essentials/Http/Controllers/DocumentShareController.php',
                        'Modules/Essentials/Resources/views/todo',
                        'Modules/Essentials/Resources/views/document',
                    ],
                    'notes' => 'Todo and document flows share comment/upload/share patterns; check both when editing either.',
                ],
                [
                    'name' => 'HRM attendance and payroll',
                    'paths' => [
                        'Modules/Essentials/Http/Controllers/AttendanceController.php',
                        'Modules/Essentials/Http/Controllers/PayrollController.php',
                        'Modules/Essentials/Http/Controllers/ShiftController.php',
                        'Modules/Essentials/Resources/views/attendance',
                        'Modules/Essentials/Resources/views/payroll',
                    ],
                    'notes' => 'Attendance feeds payroll; shift assignment affects both. Edit together when changing time/pay logic.',
                ],
                [
                    'name' => 'Leave management',
                    'paths' => [
                        'Modules/Essentials/Http/Controllers/EssentialsLeaveController.php',
                        'Modules/Essentials/Http/Controllers/EssentialsLeaveTypeController.php',
                        'Modules/Essentials/Resources/views/leave',
                        'Modules/Essentials/Resources/views/leave_type',
                    ],
                    'notes' => 'Leave type config drives leave request validation and status flow.',
                ],
                [
                    'name' => 'Transcript lifecycle',
                    'paths' => [
                        'Modules/Essentials/Http/Controllers/TranscriptController.php',
                        'Modules/Essentials/Utils/TranscriptUtil.php',
                        'Modules/Essentials/Utils/TranscriptTranslationUtil.php',
                        'Modules/Essentials/Http/Requests/StoreTranscriptRequest.php',
                        'Modules/Essentials/Http/Requests/PreviewTranscriptRequest.php',
                        'Modules/Essentials/Http/Requests/TranslateTranscriptRequest.php',
                        'Modules/Essentials/Resources/views/transcript',
                    ],
                    'notes' => 'Preview, store, and translate share form-request/util/view coupling.',
                ],
            ],
            'edit_bundles' => [
                [
                    'name' => 'Essentials collaboration bundle',
                    'paths' => [
                        'Modules/Essentials/Http/Controllers/ToDoController.php',
                        'Modules/Essentials/Http/Controllers/DocumentController.php',
                        'Modules/Essentials/Http/Controllers/ReminderController.php',
                        'Modules/Essentials/Http/Controllers/EssentialsMessageController.php',
                        'Modules/Essentials/Resources/views/todo',
                        'Modules/Essentials/Resources/views/document',
                        'Modules/Essentials/Resources/views/reminder',
                        'Modules/Essentials/Resources/views/messages',
                    ],
                    'notes' => 'Collaboration features (todo, docs, reminders, messages) share layout and permission patterns.',
                ],
                [
                    'name' => 'HRM payroll + attendance bundle',
                    'paths' => [
                        'Modules/Essentials/Http/Controllers/AttendanceController.php',
                        'Modules/Essentials/Http/Controllers/PayrollController.php',
                        'Modules/Essentials/Http/Controllers/ShiftController.php',
                        'Modules/Essentials/Http/Controllers/EssentialsAllowanceAndDeductionController.php',
                        'Modules/Essentials/Resources/views/attendance',
                        'Modules/Essentials/Resources/views/payroll',
                        'Modules/Essentials/Resources/views/allowance_deduction',
                    ],
                    'notes' => 'Payroll calculations depend on attendance + allowance/deduction data.',
                ],
            ],
            'tests' => [
                'Modules/Essentials/Tests',
            ],
            'verify_commands' => [
                'php artisan test --filter=Essentials',
                'php artisan route:list --name=essentials',
            ],
            'related_docs' => [
                'ai/laravel-conventions.md',
                'ai/security-and-auth.md',
                'ai/ui-components.md',
            ],
        ],
        'Mailbox' => [
            'purpose' => 'Entry map for the admin mailbox module, including inbox views, account setup, OAuth callback wiring, compose flows, and background sync.',
            'web_summary' => '`/mailbox/*` inbox, accounts, compose, and install routes',
            'api_summary' => 'auth API placeholder route for `/mailbox`',
            'index_trigger' => '`Mailbox`, inbox, Gmail OAuth, IMAP, compose',
            'index_note' => 'Local module folder present; module README exists',
            'keywords' => ['mailbox', 'gmail', 'oauth', 'imap', 'compose', 'threads', 'attachments', 'smtp', 'sync'],
            'use_when' => [
                'Inbox listing, thread view, star/trash, or attachment download behavior is changing.',
                'Account CRUD, Gmail OAuth redirect/callback, IMAP connection test, or sync issues need investigation.',
                'Compose/send flow or SMTP/Gmail client behavior is involved.',
            ],
            'start_here' => [
                'Modules/Mailbox/Routes/web.php',
                'Modules/Mailbox/Http/Controllers/MailboxController.php',
                'Modules/Mailbox/Http/Controllers/MailboxAccountController.php',
                'Modules/Mailbox/Resources/views/inbox',
            ],
            'workflows' => [
                [
                    'name' => 'Inbox and thread view',
                    'paths' => [
                        'Modules/Mailbox/Http/Controllers/MailboxController.php',
                        'Modules/Mailbox/Utils/MailboxMessageUtil.php',
                        'Modules/Mailbox/Resources/views/inbox',
                        'Modules/Mailbox/Resources/views/partials',
                    ],
                    'notes' => 'Inbox listing and thread read/star/trash behavior lives in controller + message util + inbox views.',
                ],
                [
                    'name' => 'Account setup and OAuth',
                    'paths' => [
                        'Modules/Mailbox/Http/Controllers/MailboxAccountController.php',
                        'Modules/Mailbox/Http/Controllers/MailboxOAuthController.php',
                        'Modules/Mailbox/Http/Requests/StoreMailboxAccountRequest.php',
                        'Modules/Mailbox/Http/Requests/UpdateMailboxAccountRequest.php',
                        'Modules/Mailbox/Http/Requests/TestMailboxConnectionRequest.php',
                        'Modules/Mailbox/Utils/MailboxAccountUtil.php',
                        'Modules/Mailbox/Resources/views/accounts',
                    ],
                    'notes' => 'Account CRUD + OAuth redirect/callback + connection test span controller, form requests, util, and account views.',
                ],
                [
                    'name' => 'Compose and send',
                    'paths' => [
                        'Modules/Mailbox/Http/Controllers/MailboxSendController.php',
                        'Modules/Mailbox/Http/Requests/SendMailboxMessageRequest.php',
                        'Modules/Mailbox/Utils/MailboxSendUtil.php',
                        'Modules/Mailbox/Services/SmtpMailboxSender.php',
                        'Modules/Mailbox/Services/GmailMailboxClient.php',
                    ],
                    'notes' => 'Send flow: controller validates via FormRequest, delegates to send util, which uses SMTP or Gmail client.',
                ],
                [
                    'name' => 'Background sync',
                    'paths' => [
                        'Modules/Mailbox/Utils/MailboxSyncUtil.php',
                        'Modules/Mailbox/Services/ImapMailboxClient.php',
                        'Modules/Mailbox/Services/GmailMailboxClient.php',
                        'Modules/Mailbox/Jobs/SyncMailboxAccountJob.php',
                    ],
                    'notes' => 'Sync job dispatches to sync util which uses IMAP or Gmail client per account type.',
                ],
            ],
            'edit_bundles' => [
                [
                    'name' => 'Inbox UI bundle',
                    'paths' => [
                        'Modules/Mailbox/Http/Controllers/MailboxController.php',
                        'Modules/Mailbox/Utils/MailboxMessageUtil.php',
                        'Modules/Mailbox/Resources/views/inbox',
                        'Modules/Mailbox/Resources/views/partials',
                        'Modules/Mailbox/Resources/assets/js/mailbox.js',
                        'Modules/Mailbox/Resources/assets/css/mailbox.css',
                    ],
                    'notes' => 'Inbox UI changes typically span controller, util, views, and the module JS/CSS assets.',
                ],
                [
                    'name' => 'Account management bundle',
                    'paths' => [
                        'Modules/Mailbox/Http/Controllers/MailboxAccountController.php',
                        'Modules/Mailbox/Http/Controllers/MailboxOAuthController.php',
                        'Modules/Mailbox/Http/Requests/StoreMailboxAccountRequest.php',
                        'Modules/Mailbox/Http/Requests/UpdateMailboxAccountRequest.php',
                        'Modules/Mailbox/Utils/MailboxAccountUtil.php',
                        'Modules/Mailbox/Resources/views/accounts',
                    ],
                    'notes' => 'Account setup and OAuth wiring should be edited together to avoid auth flow drift.',
                ],
            ],
            'tests' => [
                'Modules/Mailbox/Tests',
            ],
            'verify_commands' => [
                'php artisan test --filter=Mailbox',
                'php artisan route:list --name=mailbox',
            ],
            'related_docs' => [
                'Modules/Mailbox/README.md',
                'ai/laravel-conventions.md',
                'ai/security-and-auth.md',
                'ai/ui-components.md',
            ],
        ],
        'Projectauto' => [
            'purpose' => 'Entry map for Projectauto tasks, workflow builder screens, settings, and API draft/publish routes.',
            'web_summary' => '`/projectauto/tasks/*`, `/projectauto/settings/*`, `/projectauto/workflows/*`, and workflow API endpoints under `/projectauto/api/*`',
            'api_summary' => 'auth API route for `/projectauto/tasks`',
            'index_trigger' => '`Projectauto`, tasks, workflows, builder',
            'index_note' => 'Local module folder present; workflow-wizard doc exists',
            'keywords' => ['projectauto', 'workflow', 'tasks', 'from-wizard', 'validate-draft', 'publish'],
            'asset_paths' => [
                'Modules/Projectauto/Resources/assets/workflow-builder/src/main.js',
            ],
            'use_when' => [
                'Task board, workflow builder, or draft/publish API behavior is changing.',
                'Workflows fail between wizard steps and publish validation.',
            ],
            'start_here' => [
                'Modules/Projectauto/Routes/web.php',
                'Modules/Projectauto/Http/Controllers',
                'Modules/Projectauto/Resources/views',
                'Modules/Projectauto/Resources/assets/workflow-builder/src/main.js',
            ],
            'workflows' => [
                [
                    'name' => 'Workflow draft to publish',
                    'paths' => [
                        'Modules/Projectauto/Routes/web.php',
                        'Modules/Projectauto/Http/Controllers',
                        'Modules/Projectauto/Resources/assets/workflow-builder/src/main.js',
                    ],
                    'notes' => 'Workflow lifecycle bugs typically touch route wiring, controller validation, and builder JS payloads.',
                ],
                [
                    'name' => 'Task board and filters',
                    'paths' => [
                        'Modules/Projectauto/Http/Controllers',
                        'Modules/Projectauto/Resources/views',
                    ],
                    'notes' => 'Task listing behavior is usually a controller query + view filter integration issue.',
                ],
            ],
            'edit_bundles' => [
                [
                    'name' => 'Builder payload bundle',
                    'paths' => [
                        'Modules/Projectauto/Resources/assets/workflow-builder/src/main.js',
                        'Modules/Projectauto/Http/Controllers',
                    ],
                    'notes' => 'JS schema and backend validation should be edited together to avoid drift.',
                ],
            ],
            'tests' => [
                'Modules/Projectauto/Tests',
            ],
            'verify_commands' => [
                'php artisan test --filter=Projectauto',
                'php artisan route:list --name=projectauto',
            ],
            'related_docs' => [
                'ai/projectauto-workflow-wizard.md',
                'ai/laravel-conventions.md',
                'ai/security-and-auth.md',
            ],
        ],
        'StorageManager' => [
            'purpose' => 'Entry map for the warehouse and storage execution module under `/storage-manager/*`, covering inbound, putaway, outbound, transfers, cycle counts, damage, replenishment, purchasing advisory, and control tower.',
            'web_summary' => '`/storage-manager/*` routes for settings, areas, slots, inbound, putaway, outbound, transfers, counts, damage, replenishment, purchasing advisory, control tower, and reconciliation APIs',
            'index_trigger' => '`StorageManager`, warehouse, inbound, putaway, counts, outbound, WMS',
            'index_note' => 'Local module folder present; no `Routes/api.php` file in this checkout',
            'keywords' => ['storage-manager', 'control-tower', 'inbound', 'putaway', 'replenishment', 'counts', 'outbound', 'slots', 'warehouse', 'grn', 'pick', 'pack', 'ship', 'damage', 'cycle-count', 'purchasing-advisory', 'transfer'],
            'use_when' => [
                'Warehouse inbound receiving, GRN, or VAS sync behavior is changing.',
                'Putaway, slot assignment, or replenishment rule behavior needs investigation.',
                'Outbound pick/pack/ship, transfer execution, or cycle count flows are involved.',
                'Control tower dashboard, reconciliation, or purchasing advisory issues.',
            ],
            'start_here' => [
                'Modules/StorageManager/Routes/web.php',
                'Modules/StorageManager/Http/Controllers/StorageManagerController.php',
                'Modules/StorageManager/Http/Controllers/ControlTowerController.php',
                'Modules/StorageManager/Resources/views',
            ],
            'workflows' => [
                [
                    'name' => 'Inbound receiving and VAS sync',
                    'paths' => [
                        'Modules/StorageManager/Http/Controllers/InboundController.php',
                        'Modules/StorageManager/Http/Requests/ConfirmReceiptRequest.php',
                        'Modules/StorageManager/Http/Requests/SyncInboundReceiptVasRequest.php',
                        'Modules/StorageManager/Services/ReceivingService.php',
                        'Modules/StorageManager/Utils/StorageVasReceiptSyncUtil.php',
                        'Modules/StorageManager/Resources/views/inbound',
                    ],
                    'notes' => 'GRN receipt and VAS sync: controller validates, delegates to receiving service and VAS sync util.',
                ],
                [
                    'name' => 'Putaway and slot management',
                    'paths' => [
                        'Modules/StorageManager/Http/Controllers/PutawayController.php',
                        'Modules/StorageManager/Http/Controllers/StorageSlotController.php',
                        'Modules/StorageManager/Http/Controllers/StorageAreaController.php',
                        'Modules/StorageManager/Http/Requests/CompletePutawayRequest.php',
                        'Modules/StorageManager/Services/PutawayService.php',
                        'Modules/StorageManager/Resources/views/putaway',
                        'Modules/StorageManager/Resources/views/slots',
                        'Modules/StorageManager/Resources/views/areas',
                    ],
                    'notes' => 'Putaway depends on slot/area config; edit slot CRUD and putaway flow together.',
                ],
                [
                    'name' => 'Outbound pick, pack, ship',
                    'paths' => [
                        'Modules/StorageManager/Http/Controllers/OutboundExecutionController.php',
                        'Modules/StorageManager/Http/Requests/CompletePickRequest.php',
                        'Modules/StorageManager/Http/Requests/CompletePackRequest.php',
                        'Modules/StorageManager/Http/Requests/CompleteShipRequest.php',
                        'Modules/StorageManager/Services/OutboundExecutionService.php',
                        'Modules/StorageManager/Resources/views/outbound',
                    ],
                    'notes' => 'Three-step outbound: pick → pack → ship with separate form requests and one execution service.',
                ],
                [
                    'name' => 'Transfer execution',
                    'paths' => [
                        'Modules/StorageManager/Http/Controllers/TransferExecutionController.php',
                        'Modules/StorageManager/Http/Requests/CompleteTransferDispatchRequest.php',
                        'Modules/StorageManager/Http/Requests/ConfirmTransferReceiptRequest.php',
                        'Modules/StorageManager/Services/TransferExecutionService.php',
                        'Modules/StorageManager/Resources/views/transfers',
                    ],
                    'notes' => 'Inter-warehouse transfers: dispatch and receipt confirmation through one service.',
                ],
                [
                    'name' => 'Cycle counts and damage',
                    'paths' => [
                        'Modules/StorageManager/Http/Controllers/CycleCountController.php',
                        'Modules/StorageManager/Http/Controllers/DamageQuarantineController.php',
                        'Modules/StorageManager/Services/CycleCountService.php',
                        'Modules/StorageManager/Services/DamageQuarantineService.php',
                        'Modules/StorageManager/Resources/views/counts',
                        'Modules/StorageManager/Resources/views/damage',
                    ],
                    'notes' => 'Count sessions and damage reports share approval/shortage patterns.',
                ],
                [
                    'name' => 'Control tower and reconciliation',
                    'paths' => [
                        'Modules/StorageManager/Http/Controllers/ControlTowerController.php',
                        'Modules/StorageManager/Services/WarehouseKpiService.php',
                        'Modules/StorageManager/Services/ReconciliationService.php',
                        'Modules/StorageManager/Resources/views/control_tower',
                    ],
                    'notes' => 'Control tower dashboard with KPI widgets plus JSON reconciliation/sync-retry endpoints.',
                ],
            ],
            'edit_bundles' => [
                [
                    'name' => 'Inbound + VAS bundle',
                    'paths' => [
                        'Modules/StorageManager/Http/Controllers/InboundController.php',
                        'Modules/StorageManager/Services/ReceivingService.php',
                        'Modules/StorageManager/Utils/StorageVasReceiptSyncUtil.php',
                        'Modules/StorageManager/Resources/views/inbound',
                    ],
                    'notes' => 'Inbound receipt and VAS posting should be edited together to avoid sync drift.',
                ],
                [
                    'name' => 'Warehouse structure bundle',
                    'paths' => [
                        'Modules/StorageManager/Http/Controllers/StorageAreaController.php',
                        'Modules/StorageManager/Http/Controllers/StorageSlotController.php',
                        'Modules/StorageManager/Http/Controllers/StorageLocationSettingsController.php',
                        'Modules/StorageManager/Resources/views/areas',
                        'Modules/StorageManager/Resources/views/slots',
                        'Modules/StorageManager/Resources/views/settings',
                    ],
                    'notes' => 'Area/slot/settings CRUD share location scoping and affect all warehouse flows.',
                ],
                [
                    'name' => 'Outbound execution bundle',
                    'paths' => [
                        'Modules/StorageManager/Http/Controllers/OutboundExecutionController.php',
                        'Modules/StorageManager/Services/OutboundExecutionService.php',
                        'Modules/StorageManager/Resources/views/outbound',
                    ],
                    'notes' => 'Pick/pack/ship controller, service, and views should stay in sync.',
                ],
            ],
            'tests' => [
                'Modules/StorageManager/Tests',
            ],
            'verify_commands' => [
                'php artisan test --filter=StorageManager',
                'php artisan route:list --name=storage-manager',
            ],
            'related_docs' => [
                'ai/laravel-conventions.md',
                'ai/security-and-auth.md',
                'ai/database-map.md',
            ],
        ],
        'VasAccounting' => [
            'purpose' => 'Entry map for the `vas-accounting` module, including web UI routes, API routes, and finance controller surfaces.',
            'web_summary' => '`/vas-accounting/*` web routes for setup, vouchers, treasury, invoices, reports, integrations, closing, cutover, and more',
            'api_summary' => '`/vas-accounting/*` API routes for health, domains, posting previews, finance documents, treasury reconciliation, and provider webhooks',
            'index_trigger' => '`VasAccounting`, `vas-accounting`, vouchers, budgets, reports',
            'index_note' => 'Local module folder present; API controller subfolder exists',
            'keywords' => ['vas-accounting', 'voucher', 'cash-bank', 'payment-documents', 'procurement', 'closing', 'budget', 'integration', 'treasury'],
            'use_when' => [
                'Accounting domain screen, report, or workflow behavior is changing.',
                'Need to locate route/controller/view/lang/test surfaces for a vague accounting task.',
            ],
            'start_here' => [
                'Modules/VasAccounting/Routes/web.php',
                'Modules/VasAccounting/Http/Controllers/DashboardController.php',
                'Modules/VasAccounting/Resources/views/dashboard',
                'Modules/VasAccounting/Resources/lang/en/lang.php',
                'Modules/VasAccounting/Resources/lang/vi/lang.php',
            ],
            'workflows' => [
                [
                    'name' => 'Dashboard workspace + widgets',
                    'paths' => [
                        'Modules/VasAccounting/Http/Controllers/DashboardController.php',
                        'Modules/VasAccounting/Resources/views/dashboard',
                        'Modules/VasAccounting/Resources/views/partials',
                    ],
                    'notes' => 'Dashboard changes commonly pair controller data shaping with partial widget rendering.',
                ],
                [
                    'name' => 'Inventory document form',
                    'paths' => [
                        'Modules/VasAccounting/Http/Controllers/InventoryController.php',
                        'Modules/VasAccounting/Http/Requests',
                        'Modules/VasAccounting/Resources/views/inventory',
                        'Modules/VasAccounting/Resources/lang',
                    ],
                    'notes' => 'Form bugs often span request validation, controller defaults, Blade keys, and translation keys.',
                ],
                [
                    'name' => 'Report table/snapshot behavior',
                    'paths' => [
                        'Modules/VasAccounting/Http/Controllers/ReportController.php',
                        'Modules/VasAccounting/Resources/views/reports',
                        'Modules/VasAccounting/Routes/web.php',
                    ],
                    'notes' => 'Report issues usually combine route, controller query/filtering, and table rendering.',
                ],
                [
                    'name' => 'Treasury and payment documents',
                    'paths' => [
                        'Modules/VasAccounting/Http/Controllers/CashBankController.php',
                        'Modules/VasAccounting/Http/Controllers/PaymentDocumentController.php',
                        'Modules/VasAccounting/Resources/views/cash_bank',
                        'Modules/VasAccounting/Resources/views/payment_documents',
                    ],
                    'notes' => 'Cash/bank and payment flows often share posting and status semantics.',
                ],
            ],
            'edit_bundles' => [
                [
                    'name' => 'Inventory bug bundle',
                    'paths' => [
                        'Modules/VasAccounting/Routes/web.php',
                        'Modules/VasAccounting/Http/Controllers/InventoryController.php',
                        'Modules/VasAccounting/Http/Requests',
                        'Modules/VasAccounting/Resources/views/inventory',
                        'Modules/VasAccounting/Resources/lang/en/lang.php',
                        'Modules/VasAccounting/Resources/lang/vi/lang.php',
                        'Modules/VasAccounting/Tests',
                    ],
                    'notes' => 'Use when fixing inventory form and translation regressions quickly and safely.',
                ],
                [
                    'name' => 'Dashboard UI bundle',
                    'paths' => [
                        'Modules/VasAccounting/Http/Controllers/DashboardController.php',
                        'Modules/VasAccounting/Resources/views/dashboard',
                        'Modules/VasAccounting/Resources/views/partials',
                    ],
                    'notes' => 'UI changes should keep data contract and partial structure aligned.',
                ],
                [
                    'name' => 'Report datatable bundle',
                    'paths' => [
                        'Modules/VasAccounting/Http/Controllers/ReportController.php',
                        'Modules/VasAccounting/Resources/views/reports',
                        'Modules/VasAccounting/Routes/web.php',
                        'Modules/VasAccounting/Tests',
                    ],
                    'notes' => 'Table schema and endpoint filtering should be changed with verification in one pass.',
                ],
            ],
            'tests' => [
                'Modules/VasAccounting/Tests',
            ],
            'verify_commands' => [
                'php artisan test --filter=VasAccounting',
                'php artisan route:list --name=vasaccounting',
            ],
            'related_docs' => [
                'ai/laravel-conventions.md',
                'ai/security-and-auth.md',
                'ai/database-map.md',
            ],
        ],
    ],
];

