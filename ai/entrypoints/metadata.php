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
            'purpose' => 'Storefront and CMS-admin entry map for root shopping pages, blogs, public contact pages, and `/cms/*` admin settings.',
            'web_summary' => 'storefront routes for `/`, `/shop/*`, backward-compatible `/c/*` redirects, and admin routes under `/cms/*`',
            'api_summary' => 'auth API placeholder route for `/cms`',
            'index_trigger' => '`Cms`, storefront, `shop/*`, blog, contact-us',
            'index_note' => 'Deepest storefront map in this folder',
            'keywords' => ['cms.home', 'shop/', 'cms-page', 'site-details', 'decor-store', 'contact-us', 'blog'],
            'related_docs' => [
                'ai/ui-components.md',
                'ai/laravel-conventions.md',
                'ai/security-and-auth.md',
            ],
            'start_here' => [
                'Modules/Cms/Routes/web.php',
                'Modules/Cms/Http/Controllers/CmsController.php',
                'Modules/Cms/Resources/views/frontend',
            ],
        ],
        'Essentials' => [
            'purpose' => 'Entry map for the Essentials and HRM module surfaces under `/essentials/*` and `/hrm/*`.',
            'web_summary' => 'dashboards, documents, todos, reminders, messaging, knowledge base, transcripts, and HRM routes',
            'api_summary' => 'present but empty placeholder in this checkout',
            'index_trigger' => '`Essentials`, HRM, attendance, payroll, leave, todo',
            'index_note' => 'Local module folder present; `Routes/api.php` exists but is empty in this checkout',
            'keywords' => ['essentials', 'hrm', 'attendance', 'payroll', 'leave', 'todo', 'knowledge-base', 'transcripts'],
            'related_docs' => [
                'ai/laravel-conventions.md',
                'ai/security-and-auth.md',
                'ai/ui-components.md',
            ],
            'start_here' => [
                'Modules/Essentials/Routes/web.php',
                'Modules/Essentials/Http/Controllers',
                'Modules/Essentials/Resources/views',
            ],
        ],
        'Mailbox' => [
            'purpose' => 'Entry map for the admin mailbox module, including inbox views, account setup, OAuth callback wiring, and compose flows.',
            'web_summary' => '`/mailbox/*` inbox, accounts, compose, and install routes',
            'api_summary' => 'auth API placeholder route for `/mailbox`',
            'index_trigger' => '`Mailbox`, inbox, Gmail OAuth, IMAP, compose',
            'index_note' => 'Local module folder present; module README exists',
            'keywords' => ['mailbox', 'gmail', 'oauth', 'imap', 'compose', 'threads', 'attachments'],
            'related_docs' => [
                'Modules/Mailbox/README.md',
                'ai/laravel-conventions.md',
                'ai/security-and-auth.md',
                'ai/ui-components.md',
            ],
            'start_here' => [
                'Modules/Mailbox/Routes/web.php',
                'Modules/Mailbox/Http/Controllers',
                'Modules/Mailbox/Resources/views',
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
            'purpose' => 'Entry map for the warehouse and storage execution module under `/storage-manager/*`.',
            'web_summary' => '`/storage-manager/*` routes for settings, areas, slots, inbound, putaway, outbound, transfers, counts, damage, replenishment, and control tower',
            'index_trigger' => '`StorageManager`, warehouse, inbound, putaway, counts',
            'index_note' => 'Local module folder present; no `Routes/api.php` file in this checkout',
            'keywords' => ['storage-manager', 'control-tower', 'inbound', 'putaway', 'replenishment', 'counts', 'outbound', 'slots'],
            'related_docs' => [
                'ai/laravel-conventions.md',
                'ai/security-and-auth.md',
                'ai/database-map.md',
            ],
            'start_here' => [
                'Modules/StorageManager/Routes/web.php',
                'Modules/StorageManager/Http/Controllers',
                'Modules/StorageManager/Resources/views',
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

