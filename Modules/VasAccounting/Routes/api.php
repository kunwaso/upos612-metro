<?php

use Illuminate\Support\Facades\Route;
use Modules\VasAccounting\Http\Controllers\Api\EnterpriseApiController;
use Modules\VasAccounting\Http\Controllers\Api\FinanceDocumentController;
use Modules\VasAccounting\Http\Controllers\Api\PostingPreviewController;
use Modules\VasAccounting\Http\Controllers\Api\TreasuryExceptionController;
use Modules\VasAccounting\Http\Controllers\Api\TreasuryReconciliationController;
use Modules\VasAccounting\Http\Middleware\ApplyVasLocale;

Route::middleware(['auth:api', ApplyVasLocale::class])->prefix('vas-accounting')->name('vasaccounting.api.')->group(function () {
    Route::get('/health', [EnterpriseApiController::class, 'health'])->name('health');
    Route::get('/domains', [EnterpriseApiController::class, 'domains'])->name('domains');
    Route::get('/snapshots', [EnterpriseApiController::class, 'snapshots'])->name('snapshots');
    Route::get('/integration-runs', [EnterpriseApiController::class, 'integrationRuns'])->name('integration_runs');
    Route::post('/posting/preview', PostingPreviewController::class)->name('posting.preview');
    Route::post('/finance-documents/{family}', [FinanceDocumentController::class, 'store'])->name('finance_documents.store');
    Route::post('/finance-documents/{document}/submit', [FinanceDocumentController::class, 'submit'])->name('finance_documents.submit');
    Route::post('/finance-documents/{document}/approve', [FinanceDocumentController::class, 'approve'])->name('finance_documents.approve');
    Route::post('/finance-documents/{document}/match', [FinanceDocumentController::class, 'match'])->name('finance_documents.match');
    Route::post('/finance-documents/{document}/fulfill', [FinanceDocumentController::class, 'fulfill'])->name('finance_documents.fulfill');
    Route::post('/finance-documents/{document}/close', [FinanceDocumentController::class, 'close'])->name('finance_documents.close');
    Route::post('/finance-documents/{document}/post', [FinanceDocumentController::class, 'post'])->name('finance_documents.post');
    Route::post('/finance-documents/{document}/reverse', [FinanceDocumentController::class, 'reverse'])->name('finance_documents.reverse');
    Route::get('/finance-documents/{document}/trace', [FinanceDocumentController::class, 'trace'])->name('finance_documents.trace');
    Route::get('/treasury/statement-lines/{statementLine}/candidates', [TreasuryReconciliationController::class, 'candidates'])->name('treasury.statement_lines.candidates');
    Route::post('/treasury/statement-lines/{statementLine}/reconcile', [TreasuryReconciliationController::class, 'reconcile'])->name('treasury.statement_lines.reconcile');
    Route::post('/treasury/reconciliations/{reconciliation}/reverse', [TreasuryReconciliationController::class, 'reverse'])->name('treasury.reconciliations.reverse');
    Route::get('/treasury/exceptions', [TreasuryExceptionController::class, 'index'])->name('treasury.exceptions.index');
    Route::post('/treasury/statement-lines/{statementLine}/refresh-exception', [TreasuryExceptionController::class, 'refresh'])->name('treasury.statement_lines.refresh_exception');
    Route::post('/webhooks/{provider}', [EnterpriseApiController::class, 'webhook'])->name('webhooks.store');
});
