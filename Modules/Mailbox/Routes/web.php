<?php

use Modules\Mailbox\Http\Controllers\InstallController;
use Modules\Mailbox\Http\Controllers\MailboxAccountController;
use Modules\Mailbox\Http\Controllers\MailboxController;
use Modules\Mailbox\Http\Controllers\MailboxOAuthController;
use Modules\Mailbox\Http\Controllers\MailboxSendController;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'two_factor.verified'])
    ->prefix('mailbox')
    ->name('mailbox.')
    ->group(function () {
        Route::middleware('superadmin')->prefix('install')->name('install.')->group(function () {
            Route::get('/', [InstallController::class, 'index'])->name('index');
            Route::get('/update', [InstallController::class, 'update'])->name('update');
            Route::get('/uninstall', [InstallController::class, 'uninstall'])->name('uninstall');
        });

        Route::middleware('can:mailbox.view')->group(function () {
            Route::get('/', [MailboxController::class, 'index'])->name('index');
            Route::get('/threads/{message}', [MailboxController::class, 'showThread'])->whereNumber('message')->name('threads.show');
            Route::post('/messages/{message}/read', [MailboxController::class, 'toggleRead'])->whereNumber('message')->name('messages.read');
            Route::post('/messages/{message}/star', [MailboxController::class, 'toggleStar'])->whereNumber('message')->name('messages.star');
            Route::post('/messages/{message}/trash', [MailboxController::class, 'moveToTrash'])->whereNumber('message')->name('messages.trash');
            Route::get('/attachments/{attachment}/download', [MailboxController::class, 'downloadAttachment'])->whereNumber('attachment')->name('attachments.download');
        });

        Route::middleware('can:mailbox.manage_accounts')->prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/', [MailboxAccountController::class, 'index'])->name('index');
            Route::post('/imap/test', [MailboxAccountController::class, 'testConnection'])->name('test');
            Route::post('/imap', [MailboxAccountController::class, 'store'])->name('store');
            Route::put('/{account}', [MailboxAccountController::class, 'update'])->whereNumber('account')->name('update');
            Route::delete('/{account}', [MailboxAccountController::class, 'destroy'])->whereNumber('account')->name('destroy');
            Route::post('/{account}/sync', [MailboxAccountController::class, 'sync'])->whereNumber('account')->name('sync');
            Route::get('/oauth/google/redirect', [MailboxOAuthController::class, 'redirect'])->name('oauth.google.redirect');
            Route::get('/oauth/google/callback', [MailboxOAuthController::class, 'callback'])->name('oauth.google.callback');
        });

        Route::middleware('can:mailbox.send')->prefix('compose')->name('compose.')->group(function () {
            Route::get('/', [MailboxSendController::class, 'create'])->name('create');
            Route::post('/', [MailboxSendController::class, 'store'])->name('store');
        });
    });
