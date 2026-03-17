<?php

Route::middleware(['web', 'setData'])->group(function () {
    Route::get('/about', [Modules\ProjectX\Http\Controllers\PublicSiteController::class, 'about'])
        ->name('public.about');
    Route::get('/services', [Modules\ProjectX\Http\Controllers\PublicSiteController::class, 'services'])
        ->name('public.services');
    Route::get('/blog', [Modules\ProjectX\Http\Controllers\PublicSiteController::class, 'blogIndex'])
        ->name('public.blog.index');
    Route::get('/contact', [Modules\ProjectX\Http\Controllers\PublicSiteController::class, 'contact'])
        ->name('public.contact');
    Route::post('/contact', [Modules\ProjectX\Http\Controllers\PublicSiteController::class, 'contactSubmit'])
        ->name('public.contact.submit');
});

Route::middleware('web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu')->group(function () {
    Route::prefix('projectx')->group(function () {
        Route::get('/install', [Modules\ProjectX\Http\Controllers\InstallController::class, 'index']);
        Route::post('/install', [Modules\ProjectX\Http\Controllers\InstallController::class, 'install']);
        Route::get('/install/uninstall', [Modules\ProjectX\Http\Controllers\InstallController::class, 'uninstall']);

        Route::get('/', [Modules\ProjectX\Http\Controllers\DashboardController::class, 'index'])->name('projectx.index');
        Route::get('/sidebar-activity', [Modules\ProjectX\Http\Controllers\DashboardController::class, 'getSidebarActivity'])->name('projectx.sidebar_activity');
        Route::get('/fabric-list', [Modules\ProjectX\Http\Controllers\ProductController::class, 'index'])->name('projectx.products');
        Route::get('/sales', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'index'])->name('projectx.sales');
        Route::get('/sales/quotes/create', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'create'])->name('projectx.quotes.create');
        Route::post('/sales/quotes', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'store'])->name('projectx.quotes.store');
        Route::get('/sales/quotes/{id}', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'show'])
            ->whereNumber('id')
            ->name('projectx.quotes.show');
        Route::get('/sales/quotes/{id}/edit', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'edit'])
            ->whereNumber('id')
            ->name('projectx.quotes.edit');
        Route::match(['put', 'patch'], '/sales/quotes/{id}', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'update'])
            ->whereNumber('id')
            ->name('projectx.quotes.update');
        Route::delete('/sales/quotes/{id}', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'destroy'])
            ->whereNumber('id')
            ->name('projectx.quotes.destroy');
        Route::post('/sales/quotes/{id}/revert-draft', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'revertToDraft'])
            ->whereNumber('id')
            ->name('projectx.quotes.revert_draft');
        Route::post('/sales/quotes/{id}/clear-signature', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'clearSignature'])
            ->whereNumber('id')
            ->name('projectx.quotes.clear_signature');
        Route::post('/sales/quotes/{id}/send', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'send'])
            ->whereNumber('id')
            ->name('projectx.quotes.send');
        Route::post('/sales/quotes/{id}/public-password', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'setPublicPassword'])
            ->whereNumber('id')
            ->name('projectx.quotes.set_public_password');
        Route::get('/sales/quotes/{id}/sell-prefill', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'sellPrefill'])
            ->whereNumber('id')
            ->name('projectx.quotes.sell_prefill');
        Route::post('/sales/quotes/{id}/release-invoice', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'releaseInvoice'])
            ->whereNumber('id')
            ->name('projectx.quotes.release_invoice');

        Route::get('/sales/orders', [Modules\ProjectX\Http\Controllers\SalesController::class, 'index'])->name('projectx.sales.orders.index');
        Route::get('/sales/orders/product-search', [Modules\ProjectX\Http\Controllers\SalesController::class, 'productSearch'])
            ->name('projectx.sales.orders.product_search');
        Route::get('/sales/orders/{id}/edit', [Modules\ProjectX\Http\Controllers\SalesController::class, 'edit'])
            ->whereNumber('id')
            ->name('projectx.sales.orders.edit');
        Route::match(['put', 'patch'], '/sales/orders/{id}', [Modules\ProjectX\Http\Controllers\SalesController::class, 'update'])
            ->whereNumber('id')
            ->name('projectx.sales.orders.update');
        Route::get('/sales/orders/{id}', [Modules\ProjectX\Http\Controllers\SalesController::class, 'show'])
            ->whereNumber('id')
            ->name('projectx.sales.orders.show');
        Route::patch('/sales/orders/{id}/hold', [Modules\ProjectX\Http\Controllers\SalesController::class, 'updateHoldStatus'])
            ->whereNumber('id')
            ->name('projectx.sales.orders.hold.update');

        Route::get('/settings/quotes', [Modules\ProjectX\Http\Controllers\QuoteSettingsController::class, 'edit'])
            ->name('projectx.settings.quotes.edit');
        Route::patch('/settings/quotes', [Modules\ProjectX\Http\Controllers\QuoteSettingsController::class, 'update'])
            ->name('projectx.settings.quotes.update');

        Route::get('/chat', [Modules\ProjectX\Http\Controllers\ChatController::class, 'index'])
            ->name('projectx.chat.index');
        Route::get('/chat/config', [Modules\ProjectX\Http\Controllers\ChatController::class, 'config'])
            ->name('projectx.chat.config');
        Route::get('/chat/conversations', [Modules\ProjectX\Http\Controllers\ChatController::class, 'conversations'])
            ->name('projectx.chat.conversations.index');
        Route::post('/chat/conversations', [Modules\ProjectX\Http\Controllers\ChatController::class, 'storeConversation'])
            ->name('projectx.chat.conversations.store');
        Route::delete('/chat/conversations/{id}', [Modules\ProjectX\Http\Controllers\ChatController::class, 'destroyConversation'])
            ->whereUuid('id')
            ->name('projectx.chat.conversations.destroy');
        Route::get('/chat/conversations/{id}', [Modules\ProjectX\Http\Controllers\ChatController::class, 'showConversation'])
            ->name('projectx.chat.conversations.show');
        Route::post('/chat/conversations/{id}/send', [Modules\ProjectX\Http\Controllers\ChatController::class, 'send'])
            ->middleware('throttle:' . ((int) config('projectx.chat.throttle_per_minute', 30)) . ',1')
            ->name('projectx.chat.conversations.send');
        Route::post('/chat/conversations/{id}/stream', [Modules\ProjectX\Http\Controllers\ChatController::class, 'stream'])
            ->middleware('throttle:' . ((int) config('projectx.chat.throttle_per_minute', 30)) . ',1')
            ->name('projectx.chat.conversations.stream');
        Route::post('/chat/messages/{message}/feedback', [Modules\ProjectX\Http\Controllers\ChatController::class, 'feedback'])
            ->name('projectx.chat.messages.feedback.store');
        Route::post('/chat/messages/{message}/regenerate', [Modules\ProjectX\Http\Controllers\ChatController::class, 'regenerate'])
            ->middleware('throttle:' . ((int) config('projectx.chat.throttle_per_minute', 30)) . ',1')
            ->name('projectx.chat.messages.regenerate');
        Route::post('/chat/conversations/{id}/share', [Modules\ProjectX\Http\Controllers\ChatController::class, 'share'])
            ->name('projectx.chat.conversations.share');
        Route::get('/chat/conversations/{id}/export', [Modules\ProjectX\Http\Controllers\ChatController::class, 'export'])
            ->name('projectx.chat.conversations.export');
        Route::post('/chat/fabrics/{fabric_id}/messages/{message}/apply-updates', [Modules\ProjectX\Http\Controllers\ChatController::class, 'applyFabricUpdates'])
            ->whereNumber('fabric_id')
            ->whereNumber('message')
            ->name('projectx.chat.fabric_updates.apply');

        Route::get('/contacts', [Modules\ProjectX\Http\Controllers\ContactController::class, 'index'])->name('projectx.contacts.index');
        Route::get('/contacts/create', [Modules\ProjectX\Http\Controllers\ContactController::class, 'create'])->name('projectx.contacts.create');
        Route::post('/contacts', [Modules\ProjectX\Http\Controllers\ContactController::class, 'store'])->name('projectx.contacts.store');
        Route::get('/contacts/{id}', [Modules\ProjectX\Http\Controllers\ContactController::class, 'show'])->whereNumber('id')->name('projectx.contacts.show');
        Route::get('/contacts/{id}/edit', [Modules\ProjectX\Http\Controllers\ContactController::class, 'edit'])->whereNumber('id')->name('projectx.contacts.edit');
        Route::match(['put', 'patch'], '/contacts/{id}', [Modules\ProjectX\Http\Controllers\ContactController::class, 'update'])->whereNumber('id')->name('projectx.contacts.update');
        Route::delete('/contacts/{id}', [Modules\ProjectX\Http\Controllers\ContactController::class, 'destroy'])->whereNumber('id')->name('projectx.contacts.destroy');

        Route::prefix('essentials')->name('projectx.essentials.')->group(function () {
            Route::resource('todo', Modules\ProjectX\Http\Controllers\EssentialsTodoController::class);
            Route::post('todo/comments', [Modules\ProjectX\Http\Controllers\EssentialsTodoController::class, 'addComment'])
                ->name('todo.comments.store');
            Route::delete('todo/comments/{id}', [Modules\ProjectX\Http\Controllers\EssentialsTodoController::class, 'deleteComment'])
                ->whereNumber('id')
                ->name('todo.comments.destroy');
            Route::post('todo/documents', [Modules\ProjectX\Http\Controllers\EssentialsTodoController::class, 'uploadDocument'])
                ->name('todo.documents.store');
            Route::delete('todo/documents/{id}', [Modules\ProjectX\Http\Controllers\EssentialsTodoController::class, 'deleteDocument'])
                ->whereNumber('id')
                ->name('todo.documents.destroy');
            Route::get('todo/{todo}/shared-docs', [Modules\ProjectX\Http\Controllers\EssentialsTodoController::class, 'viewSharedDocs'])
                ->whereNumber('todo')
                ->name('todo.shared-docs');

            Route::get('documents/{id}/download', [Modules\ProjectX\Http\Controllers\EssentialsDocumentController::class, 'download'])
                ->whereNumber('id')
                ->name('documents.download');
            Route::resource('documents', Modules\ProjectX\Http\Controllers\EssentialsDocumentController::class)
                ->only(['index', 'store', 'show', 'destroy']);

            Route::get('document-share/{id}/edit', [Modules\ProjectX\Http\Controllers\EssentialsDocumentShareController::class, 'edit'])
                ->whereNumber('id')
                ->name('document-share.edit');
            Route::match(['put', 'patch'], 'document-share/{id}', [Modules\ProjectX\Http\Controllers\EssentialsDocumentShareController::class, 'update'])
                ->whereNumber('id')
                ->name('document-share.update');

            Route::resource('reminders', Modules\ProjectX\Http\Controllers\EssentialsReminderController::class)
                ->only(['index', 'store', 'show', 'update', 'destroy']);

            Route::get('messages/new', [Modules\ProjectX\Http\Controllers\EssentialsMessageController::class, 'getNewMessages'])
                ->name('messages.get-new');
            Route::resource('messages', Modules\ProjectX\Http\Controllers\EssentialsMessageController::class)
                ->only(['index', 'store', 'destroy']);

            Route::resource('knowledge-base', Modules\ProjectX\Http\Controllers\EssentialsKnowledgeBaseController::class);

            Route::get('settings', [Modules\ProjectX\Http\Controllers\EssentialsSettingsController::class, 'edit'])
                ->name('settings.edit');
            Route::post('settings', [Modules\ProjectX\Http\Controllers\EssentialsSettingsController::class, 'update'])
                ->name('settings.update');

            Route::resource('allowance-deduction', Modules\ProjectX\Http\Controllers\ProjectXEssentialsAllowanceAndDeductionController::class);

            Route::prefix('hrm')->name('hrm.')->group(function () {
                Route::get('dashboard', [Modules\ProjectX\Http\Controllers\ProjectXHrmDashboardController::class, 'hrmDashboard'])
                    ->name('dashboard');
                Route::get('user-sales-targets', [Modules\ProjectX\Http\Controllers\ProjectXHrmDashboardController::class, 'getUserSalesTargets'])
                    ->name('user-sales-targets');

                Route::resource('leave-type', Modules\ProjectX\Http\Controllers\ProjectXEssentialsLeaveTypeController::class);
                Route::resource('leave', Modules\ProjectX\Http\Controllers\ProjectXEssentialsLeaveController::class);
                Route::post('leave/change-status', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsLeaveController::class, 'changeStatus'])
                    ->name('leave.change-status');
                Route::get('leave/activity/{id}', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsLeaveController::class, 'activity'])
                    ->whereNumber('id')
                    ->name('leave.activity');
                Route::get('leave/user-leave-summary', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsLeaveController::class, 'getUserLeaveSummary'])
                    ->name('leave.user-leave-summary');
                Route::get('leave/change-leave-status', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsLeaveController::class, 'changeLeaveStatus'])
                    ->name('leave.change-leave-status');

                Route::post('import-attendance', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsAttendanceController::class, 'importAttendance'])
                    ->name('attendance.import');
                Route::resource('attendance', Modules\ProjectX\Http\Controllers\ProjectXEssentialsAttendanceController::class);
                Route::post('clock-in-clock-out', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsAttendanceController::class, 'clockInClockOut'])
                    ->name('attendance.clock-in-clock-out');
                Route::post('validate-clock-in-clock-out', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsAttendanceController::class, 'validateClockInClockOut'])
                    ->name('attendance.validate-clock-in-clock-out');
                Route::get('get-attendance-by-shift', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsAttendanceController::class, 'getAttendanceByShift'])
                    ->name('attendance.get-attendance-by-shift');
                Route::get('get-attendance-by-date', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsAttendanceController::class, 'getAttendanceByDate'])
                    ->name('attendance.get-attendance-by-date');
                Route::get('get-attendance-row/{user_id}', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsAttendanceController::class, 'getAttendanceRow'])
                    ->whereNumber('user_id')
                    ->name('attendance.get-attendance-row');
                Route::get('user-attendance-summary', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsAttendanceController::class, 'getUserAttendanceSummary'])
                    ->name('attendance.user-attendance-summary');

                Route::resource('shift', Modules\ProjectX\Http\Controllers\ProjectXEssentialsShiftController::class);
                Route::get('shift/assign-users/{shift_id}', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsShiftController::class, 'getAssignUsers'])
                    ->whereNumber('shift_id')
                    ->name('shift.assign-users');
                Route::post('shift/assign-users', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsShiftController::class, 'postAssignUsers'])
                    ->name('shift.assign-users.store');

                Route::resource('holiday', Modules\ProjectX\Http\Controllers\ProjectXEssentialsHolidayController::class);

                Route::get('location-employees', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsPayrollController::class, 'getEmployeesBasedOnLocation'])
                    ->name('payroll.location-employees');
                Route::get('my-payrolls', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsPayrollController::class, 'getMyPayrolls'])
                    ->name('payroll.my-payrolls');
                Route::get('get-allowance-deduction-row', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsPayrollController::class, 'getAllowanceAndDeductionRow'])
                    ->name('payroll.get-allowance-deduction-row');
                Route::get('payroll-group-datatable', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsPayrollController::class, 'payrollGroupDatatable'])
                    ->name('payroll.payroll-group-datatable');
                Route::get('view/{id}/payroll-group', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsPayrollController::class, 'viewPayrollGroup'])
                    ->whereNumber('id')
                    ->name('payroll.view-payroll-group');
                Route::get('edit/{id}/payroll-group', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsPayrollController::class, 'getEditPayrollGroup'])
                    ->whereNumber('id')
                    ->name('payroll.edit-payroll-group');
                Route::post('update-payroll-group', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsPayrollController::class, 'getUpdatePayrollGroup'])
                    ->name('payroll.update-payroll-group');
                Route::get('payroll-group/{id}/add-payment', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsPayrollController::class, 'addPayment'])
                    ->whereNumber('id')
                    ->name('payroll.add-payment');
                Route::post('post-payment-payroll-group', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsPayrollController::class, 'postAddPayment'])
                    ->name('payroll.post-payment-payroll-group');
                Route::resource('payroll', Modules\ProjectX\Http\Controllers\ProjectXEssentialsPayrollController::class);

                Route::get('sales-target', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsSalesTargetController::class, 'index'])
                    ->name('sales-target.index');
                Route::get('set-sales-target/{id}', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsSalesTargetController::class, 'setSalesTarget'])
                    ->whereNumber('id')
                    ->name('sales-target.set');
                Route::post('save-sales-target', [Modules\ProjectX\Http\Controllers\ProjectXEssentialsSalesTargetController::class, 'saveSalesTarget'])
                    ->name('sales-target.save');
            });
        });

        Route::get('/user-profile', [Modules\ProjectX\Http\Controllers\UserProfileController::class, 'index'])
            ->name('projectx.user_profile.index');
        Route::patch('/user-profile', [Modules\ProjectX\Http\Controllers\UserProfileController::class, 'update'])
            ->name('projectx.user_profile.update');
        Route::patch('/user-profile/password', [Modules\ProjectX\Http\Controllers\UserProfileController::class, 'updatePassword'])
            ->name('projectx.user_profile.password.update');
        Route::patch('/user-profile/lock-screen-pin', [Modules\ProjectX\Http\Controllers\UserProfileController::class, 'updateLockScreenPin'])
            ->name('projectx.user_profile.lock_screen_pin.update');
        Route::post('/user-profile/tasks', [Modules\ProjectX\Http\Controllers\UserProfileController::class, 'storeTask'])
            ->name('projectx.user_profile.tasks.store');
        Route::patch('/user-profile/tasks/{task}', [Modules\ProjectX\Http\Controllers\UserProfileController::class, 'updateTask'])
            ->whereNumber('task')
            ->name('projectx.user_profile.tasks.update');
        Route::delete('/user-profile/tasks/{task}', [Modules\ProjectX\Http\Controllers\UserProfileController::class, 'destroyTask'])
            ->whereNumber('task')
            ->name('projectx.user_profile.tasks.destroy');
        Route::patch('/user-profile/heatmap-overrides', [Modules\ProjectX\Http\Controllers\UserProfileController::class, 'upsertHeatmapOverride'])
            ->name('projectx.user_profile.heatmap_overrides.upsert');
        Route::delete('/user-profile/heatmap-overrides', [Modules\ProjectX\Http\Controllers\UserProfileController::class, 'destroyHeatmapOverride'])
            ->name('projectx.user_profile.heatmap_overrides.destroy');

        Route::get('/chat/settings', [Modules\ProjectX\Http\Controllers\ChatSettingsController::class, 'index'])
            ->name('projectx.chat.settings');
        Route::post('/chat/settings/credential', [Modules\ProjectX\Http\Controllers\ChatSettingsController::class, 'storeCredential'])
            ->name('projectx.chat.settings.credential.store');
        Route::patch('/chat/settings/business', [Modules\ProjectX\Http\Controllers\ChatSettingsController::class, 'updateBusiness'])
            ->name('projectx.chat.settings.business.update');
        Route::post('/chat/settings/memory', [Modules\ProjectX\Http\Controllers\ChatSettingsController::class, 'storeMemory'])
            ->name('projectx.chat.settings.memory.store');
        Route::patch('/chat/settings/memory/{memory}', [Modules\ProjectX\Http\Controllers\ChatSettingsController::class, 'updateMemory'])
            ->whereNumber('memory')
            ->name('projectx.chat.settings.memory.update');
        Route::delete('/chat/settings/memory/{memory}', [Modules\ProjectX\Http\Controllers\ChatSettingsController::class, 'destroyMemory'])
            ->whereNumber('memory')
            ->name('projectx.chat.settings.memory.destroy');

        Route::get('/site-manager', [Modules\ProjectX\Http\Controllers\SiteManagerController::class, 'index'])
            ->name('projectx.site_manager.index');
        Route::get('/site-manager/edit', [Modules\ProjectX\Http\Controllers\SiteManagerController::class, 'edit'])
            ->name('projectx.site_manager.edit');
        Route::match(['put', 'patch'], '/site-manager', [Modules\ProjectX\Http\Controllers\SiteManagerController::class, 'update'])
            ->name('projectx.site_manager.update');

        Route::prefix('fabric-manager')->group(function () {
            Route::get('/', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'list'])->name('projectx.fabric_manager.list');
            Route::get('/create', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'create'])->name('projectx.fabric_manager.create');
            Route::post('/', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'store'])->name('projectx.fabric_manager.store');
            Route::get('/component-catalog', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'getComponentCatalog'])->name('projectx.fabric_manager.component_catalog');
            Route::get('/pantone-tcx-catalog', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'getPantoneTcxCatalog'])->name('projectx.fabric_manager.pantone_catalog');

            // Legacy no-ID detail URLs now route users back to the list page.
            Route::get('/fabric', function () {
                return redirect()->route('projectx.fabric_manager.list');
            });
            Route::get('/datasheet', function () {
                return redirect()->route('projectx.fabric_manager.list');
            });
            Route::get('/budget', function () {
                return redirect()->route('projectx.fabric_manager.list');
            });
            Route::get('/users', function () {
                return redirect()->route('projectx.fabric_manager.list');
            });
            Route::get('/files', function () {
                return redirect()->route('projectx.fabric_manager.list');
            });
            Route::get('/activity', function () {
                return redirect()->route('projectx.fabric_manager.list');
            });
            Route::get('/settings', function () {
                return redirect()->route('projectx.fabric_manager.list');
            });

            Route::prefix('fabric/{fabric_id}')
                ->whereNumber('fabric_id')
                ->group(function () {
                    Route::get('/composition', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'getComposition'])->name('projectx.fabric_manager.composition.show');
                    Route::patch('/composition', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'updateComposition'])->name('projectx.fabric_manager.composition.update');
                    Route::get('/pantone', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'getPantone'])->name('projectx.fabric_manager.pantone.show');
                    Route::patch('/pantone', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'updatePantone'])->name('projectx.fabric_manager.pantone.update');
                    Route::post('/settings', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'updateSettings'])->name('projectx.fabric_manager.settings.update');
                    Route::get('/datasheet/pdf', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'datasheetPdf'])->name('projectx.fabric_manager.datasheet.pdf');
                    Route::patch('/share-settings', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'updateShareSettings'])->name('projectx.fabric_manager.share_settings.update');
                    Route::post('/submit-for-approval', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'submitForApproval'])->name('projectx.fabric_manager.submit_for_approval');
                    Route::post('/approve', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'approve'])->name('projectx.fabric_manager.approve');
                    Route::post('/reject', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'reject'])->name('projectx.fabric_manager.reject');
                    Route::get('/', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'fabric'])->name('projectx.fabric_manager.fabric');
                    Route::get('/datasheet', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'datasheet'])->name('projectx.fabric_manager.datasheet');
                    Route::get('/budget', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'budget'])->name('projectx.fabric_manager.budget');
                    Route::post('/quotes', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'storeFromFabric'])->name('projectx.fabric_manager.quotes.store');
                    Route::get('/users', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'users'])->name('projectx.fabric_manager.users');
                    Route::get('/files', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'files'])->name('projectx.fabric_manager.files');
                    Route::post('/files/upload', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'uploadAttachment'])->name('projectx.fabric_manager.files.upload');
                    Route::get('/files/{file_hash}/download', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'downloadAttachment'])
                        ->where('file_hash', '[A-Fa-f0-9]{64}')
                        ->name('projectx.fabric_manager.files.download');
                    Route::delete('/files/{file_hash}', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'destroyAttachment'])
                        ->where('file_hash', '[A-Fa-f0-9]{64}')
                        ->name('projectx.fabric_manager.files.delete');
                    Route::get('/activity', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'activity'])->name('projectx.fabric_manager.activity');
                    Route::delete('/activity/{log_id}', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'destroyActivityLog'])
                        ->whereNumber('log_id')
                        ->name('projectx.fabric_manager.activity.delete');
                    Route::get('/settings', [Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'settings'])->name('projectx.fabric_manager.settings');
                });
        });

        Route::prefix('trim-manager')->group(function () {
            Route::get('/', [Modules\ProjectX\Http\Controllers\TrimManagerController::class, 'list'])->name('projectx.trim_manager.list');
            Route::get('/create', [Modules\ProjectX\Http\Controllers\TrimManagerController::class, 'create'])->name('projectx.trim_manager.create');
            Route::post('/', [Modules\ProjectX\Http\Controllers\TrimManagerController::class, 'store'])->name('projectx.trim_manager.store');
            Route::post('/categories', [Modules\ProjectX\Http\Controllers\TrimManagerController::class, 'storeCategory'])->name('projectx.trim_manager.categories.store');
            Route::delete('/categories/{id}', [Modules\ProjectX\Http\Controllers\TrimManagerController::class, 'destroyCategory'])
                ->whereNumber('id')
                ->name('projectx.trim_manager.categories.destroy');

            Route::prefix('trim/{id}')
                ->whereNumber('id')
                ->group(function () {
                    Route::get('/', [Modules\ProjectX\Http\Controllers\TrimManagerController::class, 'show'])->name('projectx.trim_manager.show');
                    Route::get('/edit', [Modules\ProjectX\Http\Controllers\TrimManagerController::class, 'edit'])->name('projectx.trim_manager.edit');
                    Route::match(['put', 'patch'], '/', [Modules\ProjectX\Http\Controllers\TrimManagerController::class, 'update'])->name('projectx.trim_manager.update');
                    Route::delete('/', [Modules\ProjectX\Http\Controllers\TrimManagerController::class, 'destroy'])->name('projectx.trim_manager.destroy');
                    Route::get('/datasheet', [Modules\ProjectX\Http\Controllers\TrimManagerController::class, 'datasheet'])->name('projectx.trim_manager.datasheet');
                    Route::get('/datasheet/pdf', [Modules\ProjectX\Http\Controllers\TrimManagerController::class, 'datasheetPdf'])->name('projectx.trim_manager.datasheet.pdf');
                    Route::patch('/share-settings', [Modules\ProjectX\Http\Controllers\TrimManagerController::class, 'updateShareSettings'])->name('projectx.trim_manager.share_settings.update');
                    Route::get('/budget', [Modules\ProjectX\Http\Controllers\TrimManagerController::class, 'budget'])->name('projectx.trim_manager.budget');
                    Route::post('/quotes', [Modules\ProjectX\Http\Controllers\QuoteController::class, 'storeFromTrim'])->name('projectx.trim_manager.quotes.store');
                });
        });
    });
});

Route::middleware('web')->group(function () {
    Route::get('/q/{publicToken}', [Modules\ProjectX\Http\Controllers\PublicQuoteController::class, 'show'])
        ->name('projectx.quotes.public');
    Route::post('/q/{publicToken}/unlock', [Modules\ProjectX\Http\Controllers\PublicQuoteController::class, 'unlock'])
        ->middleware('throttle:6,1')
        ->name('projectx.quotes.public.unlock');
    Route::post('/q/{publicToken}/confirm', [Modules\ProjectX\Http\Controllers\PublicQuoteController::class, 'confirm'])
        ->name('projectx.quotes.public.confirm');

    Route::get('/fabric-datasheet/{token}', [Modules\ProjectX\Http\Controllers\FabricDatasheetShareController::class, 'show'])
        ->name('projectx.fabric_manager.datasheet.share');
    Route::post('/fabric-datasheet/{token}', [Modules\ProjectX\Http\Controllers\FabricDatasheetShareController::class, 'verifyPassword'])
        ->name('projectx.fabric_manager.datasheet.share.verify');

    Route::get('/trim-datasheet/{token}', [Modules\ProjectX\Http\Controllers\TrimDatasheetShareController::class, 'show'])
        ->name('projectx.trim_manager.datasheet.share');
    Route::post('/trim-datasheet/{token}', [Modules\ProjectX\Http\Controllers\TrimDatasheetShareController::class, 'verifyPassword'])
        ->name('projectx.trim_manager.datasheet.share.verify');

    Route::get('/projectx/chat/shared/{conversation}', [Modules\ProjectX\Http\Controllers\ChatController::class, 'sharedShow'])
        ->middleware('signed')
        ->name('projectx.chat.shared.show');
});
