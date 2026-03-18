<?php

use Modules\Aichat\Http\Controllers\ChatController;
use Modules\Aichat\Http\Controllers\ChatMemoryAdminController;
use Modules\Aichat\Http\Controllers\ChatQuoteWizardController;
use Modules\Aichat\Http\Controllers\ChatSettingsController;
use Modules\Aichat\Http\Controllers\TelegramWebhookController;

Route::middleware(['web'])->group(function () {
    Route::post('/aichat/telegram/webhook/{webhookKey}', [TelegramWebhookController::class, 'webhook'])
        ->name('aichat.telegram.webhook');
});

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'])->group(function () {
    Route::prefix('aichat')->group(function () {
        Route::prefix('chat')->name('aichat.chat.')->group(function () {
            Route::get('/', [ChatController::class, 'index'])->name('index');
            Route::get('/config', [ChatController::class, 'config'])->name('config');
            Route::get('/conversations', [ChatController::class, 'conversations'])->name('conversations.index');
            Route::post('/conversations', [ChatController::class, 'storeConversation'])->name('conversations.store');
            Route::delete('/conversations/{id}', [ChatController::class, 'destroyConversation'])->whereUuid('id')->name('conversations.destroy');
            Route::get('/conversations/{id}', [ChatController::class, 'showConversation'])->whereUuid('id')->name('conversations.show');
            Route::post('/conversations/{id}/send', [ChatController::class, 'send'])->whereUuid('id')->middleware('throttle:' . ((int) config('aichat.chat.throttle_per_minute', 30)) . ',1')->name('conversations.send');
            Route::post('/conversations/{id}/stream', [ChatController::class, 'stream'])->whereUuid('id')->middleware('throttle:' . ((int) config('aichat.chat.throttle_per_minute', 30)) . ',1')->name('conversations.stream');
            Route::prefix('/conversations/{id}/quote-wizard')->whereUuid('id')->middleware('can:aichat.quote_wizard.use')->name('conversations.quote_wizard.')->group(function () {
                Route::get('/contacts', [ChatQuoteWizardController::class, 'contacts'])->name('contacts');
                Route::get('/locations', [ChatQuoteWizardController::class, 'locations'])->name('locations');
                Route::get('/products', [ChatQuoteWizardController::class, 'products'])->name('products');
                Route::get('/costing-defaults', [ChatQuoteWizardController::class, 'costingDefaults'])->name('costing_defaults');
                Route::post('/process', [ChatQuoteWizardController::class, 'process'])
                    ->middleware('throttle:' . ((int) config('aichat.quote_wizard.process_throttle_per_minute', 30)) . ',1')
                    ->name('process');
                Route::post('/confirm', [ChatQuoteWizardController::class, 'confirm'])
                    ->middleware('throttle:' . ((int) config('aichat.quote_wizard.confirm_throttle_per_minute', 10)) . ',1')
                    ->name('confirm');
            });
            Route::post('/messages/{message}/feedback', [ChatController::class, 'feedback'])->whereNumber('message')->name('messages.feedback.store');
            Route::post('/messages/{message}/regenerate', [ChatController::class, 'regenerate'])->whereNumber('message')->middleware('throttle:' . ((int) config('aichat.chat.throttle_per_minute', 30)) . ',1')->name('messages.regenerate');
            Route::post('/conversations/{id}/share', [ChatController::class, 'share'])->whereUuid('id')->name('conversations.share');
            Route::get('/conversations/{id}/export', [ChatController::class, 'export'])->whereUuid('id')->name('conversations.export');
            Route::get('/settings', [ChatSettingsController::class, 'index'])->name('settings');
            Route::post('/settings/credential', [ChatSettingsController::class, 'storeCredential'])->name('settings.credential.store');
            Route::patch('/settings/business', [ChatSettingsController::class, 'updateBusiness'])->name('settings.business.update');
            Route::patch('/settings/profile', [ChatSettingsController::class, 'updateProfile'])->name('settings.profile.update');
            Route::post('/settings/telegram', [ChatSettingsController::class, 'storeTelegramBot'])->name('settings.telegram.store');
            Route::delete('/settings/telegram', [ChatSettingsController::class, 'destroyTelegramBot'])->name('settings.telegram.destroy');
            Route::patch('/settings/telegram/allowed-users', [ChatSettingsController::class, 'updateTelegramAllowedUsers'])->name('settings.telegram.allowed-users.update');
            Route::post('/settings/telegram/allowed-users/{user_id}/regenerate-code', [ChatSettingsController::class, 'regenerateTelegramLinkCode'])
                ->whereNumber('user_id')
                ->name('settings.telegram.allowed-users.regenerate-code');
            Route::post('/settings/telegram/allowed-groups', [ChatSettingsController::class, 'storeTelegramAllowedGroup'])->name('settings.telegram.allowed-groups.store');
            Route::delete('/settings/telegram/allowed-groups/{telegram_chat_id}', [ChatSettingsController::class, 'destroyTelegramAllowedGroup'])
                ->where('telegram_chat_id', '-?[0-9]+')
                ->name('settings.telegram.allowed-groups.destroy');
            Route::post('/settings/memory', [ChatSettingsController::class, 'storeMemory'])->name('settings.memory.store');
            Route::patch('/settings/memory/{memory}', [ChatSettingsController::class, 'updateMemory'])->whereNumber('memory')->name('settings.memory.update');
            Route::delete('/settings/memory/{memory}', [ChatSettingsController::class, 'destroyMemory'])->whereNumber('memory')->name('settings.memory.destroy');

            Route::middleware('can:aichat.manage_all_memories')->group(function () {
                Route::get('/settings/memories/admin', [ChatMemoryAdminController::class, 'index'])->name('settings.memories.admin');
                Route::patch('/settings/memories/admin/{business}/display-name', [ChatMemoryAdminController::class, 'updateName'])->whereNumber('business')->name('settings.memories.admin.updateName');
                Route::delete('/settings/memories/admin/{business}/wipe', [ChatMemoryAdminController::class, 'wipe'])->whereNumber('business')->name('settings.memories.admin.wipe');
            });
        });
    });
});

Route::middleware(['web', 'signed'])->group(function () {
    Route::get('/aichat/chat/shared/{conversation}', [ChatController::class, 'sharedShow'])
        ->whereUuid('conversation')
        ->name('aichat.chat.shared.show');
});
