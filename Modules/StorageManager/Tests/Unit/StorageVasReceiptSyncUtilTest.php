<?php

namespace Modules\StorageManager\Tests\Unit;

use Modules\StorageManager\Utils\StorageVasReceiptSyncUtil;
use Tests\TestCase;

class StorageVasReceiptSyncUtilTest extends TestCase
{
    public function test_util_class_exists_and_is_resolvable(): void
    {
        $this->assertTrue(class_exists(StorageVasReceiptSyncUtil::class));

        $instance = app(StorageVasReceiptSyncUtil::class);
        $this->assertInstanceOf(StorageVasReceiptSyncUtil::class, $instance);
    }

    public function test_unlink_method_signature_matches_contract(): void
    {
        $reflection = new \ReflectionMethod(StorageVasReceiptSyncUtil::class, 'unlinkReceiptVasSync');

        $this->assertTrue($reflection->isPublic());
        $this->assertCount(3, $reflection->getParameters());

        $params = $reflection->getParameters();
        $this->assertSame('businessId', $params[0]->getName());
        $this->assertSame('receipt', $params[1]->getName());
        $this->assertSame('userId', $params[2]->getName());
    }

    public function test_inbound_vas_sync_config_defaults_to_manual(): void
    {
        $this->assertSame('manual', config('storagemanager.inbound_vas_sync'));
    }

    public function test_unlink_rejects_non_receipt_documents(): void
    {
        $document = new \Modules\StorageManager\Entities\StorageDocument();
        $document->document_type = 'damage';
        $document->status = 'completed';

        $util = app(StorageVasReceiptSyncUtil::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only receipt or putaway documents can be unlinked from VAS accounting.');

        $util->unlinkReceiptVasSync(1, $document, 1);
    }

    public function test_unlink_rejects_non_completed_receipts(): void
    {
        $document = new \Modules\StorageManager\Entities\StorageDocument();
        $document->document_type = 'receipt';
        $document->status = 'open';

        $util = app(StorageVasReceiptSyncUtil::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Storage document must be completed or closed before unlinking accounting.');

        $util->unlinkReceiptVasSync(1, $document, 1);
    }
}
