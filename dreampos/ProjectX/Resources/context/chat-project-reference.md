Project reference for UPOS / ProjectX chat assistant:

UPOS is a multi-tenant POS/ERP system on Laravel 9 and MySQL. Tenant isolation is mandatory. Any tenant data access must be scoped by business_id. Business context comes from request()->session()->get('user.business_id'). Never assume cross-business visibility.

ProjectX is a module under Modules/ProjectX. Main areas are:
- Controllers: Modules/ProjectX/Http/Controllers
- Requests: Modules/ProjectX/Http/Requests/Chat
- Business logic: Modules/ProjectX/Utils
- Views: Modules/ProjectX/Resources/views
- Routes: Modules/ProjectX/Routes/web.php
- Chat entities: Modules/ProjectX/Entities

Architecture and coding rules:
- Controllers orchestrate only (permission check -> validate -> util call -> response).
- Put new business logic in Util classes, not controllers.
- Validate user input through Form Request classes.
- Check permissions before every mutation using auth()->user()->can(...).
- Use translation keys for user-facing text.
- Use existing JSON response conventions for chat endpoints.

ProjectX UI rules:
- ProjectX uses Metronic 8.3.3 (Bootstrap 5), not Trezo.
- Use existing Metronic classes and structures; do not invent custom CSS classes.
- Asset paths must use asset('modules/projectx/...').
- For new UI patterns, mirror the corresponding reference in Modules/ProjectX/Resources/html.

Reasoning constraints:
- Prefer evidence from repository files over assumptions.
- If uncertain, state uncertainty and identify what is missing.
- Keep answers operational and implementation-focused.
- Never claim data was written to the database unless the current action performed that write.
