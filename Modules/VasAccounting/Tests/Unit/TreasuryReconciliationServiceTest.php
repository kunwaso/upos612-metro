<?php

namespace Modules\VasAccounting\Tests\Unit;

use Carbon\Carbon;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Entities\VasBankStatementImport;
use Modules\VasAccounting\Entities\VasBankStatementLine;
use Modules\VasAccounting\Services\Treasury\TreasuryReconciliationService;
use Tests\TestCase;

class TreasuryReconciliationServiceTest extends TestCase
{
    public function test_it_maps_statement_direction_to_expected_document_types(): void
    {
        $service = new TreasuryReconciliationService();

        $this->assertSame(['customer_receipt'], $service->resolveCandidateDocumentTypes('150000.0000'));
        $this->assertSame(['supplier_payment'], $service->resolveCandidateDocumentTypes('-150000.0000'));
    }

    public function test_it_scores_exact_bank_and_reference_matches_higher(): void
    {
        $service = new TreasuryReconciliationService();
        $statementLine = $this->statementLine(5, 500, 'RCPT-1001 transfer');
        $exactCandidate = $this->document('customer_receipt', 'RCPT-1001', '500.0000', 5, '2026-04-01');
        $looseCandidate = $this->document('customer_receipt', 'RCPT-2001', '450.0000', 6, '2026-03-25');

        $exactScore = $service->scoreCandidate($statementLine, $exactCandidate);
        $looseScore = $service->scoreCandidate($statementLine, $looseCandidate);

        $this->assertTrue($exactScore['within_amount_tolerance']);
        $this->assertTrue($exactScore['reference_matched']);
        $this->assertTrue($exactScore['bank_account_matched']);
        $this->assertGreaterThan($looseScore['score'], $exactScore['score']);
    }

    protected function statementLine(int $bankAccountId, float $amount, string $description): VasBankStatementLine
    {
        $statementLine = new VasBankStatementLine([
            'id' => 900,
            'amount' => $amount,
            'description' => $description,
            'transaction_date' => Carbon::parse('2026-04-01'),
            'meta' => [],
        ]);
        $statementLine->setRelation('statementImport', new VasBankStatementImport([
            'id' => 91,
            'bank_account_id' => $bankAccountId,
        ]));

        return $statementLine;
    }

    protected function document(
        string $documentType,
        string $documentNo,
        string $amount,
        int $bankAccountId,
        string $postingDate
    ): FinanceDocument {
        return new FinanceDocument([
            'id' => random_int(100, 999),
            'document_type' => $documentType,
            'document_family' => 'cash_bank',
            'document_no' => $documentNo,
            'gross_amount' => $amount,
            'open_amount' => $amount,
            'currency_code' => 'VND',
            'posting_date' => Carbon::parse($postingDate),
            'document_date' => Carbon::parse($postingDate),
            'meta' => [
                'payment' => [
                    'bank_account_id' => $bankAccountId,
                    'reference' => $documentNo,
                ],
            ],
        ]);
    }
}
