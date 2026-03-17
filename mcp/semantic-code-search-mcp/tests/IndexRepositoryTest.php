<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Tests;

use PHPUnit\Framework\TestCase;
use SemanticCodeSearchMcp\Embeddings\DeterministicEmbedder;
use SemanticCodeSearchMcp\Index\IndexRepository;

final class IndexRepositoryTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = str_replace('\\', '/', sys_get_temp_dir().'/semantic-index-'.bin2hex(random_bytes(6)));
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->tempDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tempDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($this->tempDir);
    }

    public function test_it_replaces_file_chunks_without_duplicates(): void
    {
        $repository = new IndexRepository($this->tempDir.'/semantic.sqlite');
        $embedder = new DeterministicEmbedder();
        $vectors = $embedder->embedTexts(['invoice total amount']);

        $repository->replaceFile('app/Invoice.php', 100, 10, 'hash-a', [[
            'id' => 'chunk-a',
            'path' => 'app/Invoice.php',
            'start_line' => 1,
            'end_line' => 3,
            'content' => 'invoice total amount',
        ]], $vectors);

        $repository->replaceFile('app/Invoice.php', 101, 12, 'hash-b', [[
            'id' => 'chunk-b',
            'path' => 'app/Invoice.php',
            'start_line' => 1,
            'end_line' => 2,
            'content' => 'invoice balance',
        ]], $embedder->embedTexts(['invoice balance']));

        $repository->updateMetadata('D:/repo', 'mock-deterministic');
        $status = $repository->status('D:/repo', 'mock-deterministic');

        self::assertTrue($status['ready']);
        self::assertFalse($status['stale']);
        self::assertSame(1, $status['files_indexed']);
        self::assertSame(1, $status['chunks_indexed']);
    }

    public function test_it_searches_by_similarity_and_removes_missing_files_within_scope(): void
    {
        $repository = new IndexRepository($this->tempDir.'/semantic.sqlite');
        $embedder = new DeterministicEmbedder();

        $alphaText = 'invoice total tax amount due';
        $betaText = 'password reset token mail notification';

        $repository->replaceFile('app/Billing.php', 100, 20, 'hash-a', [[
            'id' => 'alpha',
            'path' => 'app/Billing.php',
            'start_line' => 1,
            'end_line' => 2,
            'content' => $alphaText,
        ]], $embedder->embedTexts([$alphaText]));

        $repository->replaceFile('app/Auth.php', 100, 20, 'hash-b', [[
            'id' => 'beta',
            'path' => 'app/Auth.php',
            'start_line' => 1,
            'end_line' => 2,
            'content' => $betaText,
        ]], $embedder->embedTexts([$betaText]));

        $repository->updateMetadata('D:/repo', 'mock-deterministic');

        $results = $repository->search($embedder->embedTexts(['invoice amount'])[0], 5);

        self::assertSame('app/Billing.php', $results[0]['file']);
        self::assertGreaterThan($results[1]['score'], $results[0]['score']);

        $removed = $repository->removeMissingFiles(['app/Billing.php'], 'app', false);
        $status = $repository->status('D:/repo', 'mock-deterministic');

        self::assertSame(1, $removed);
        self::assertSame(1, $status['files_indexed']);
    }
}
