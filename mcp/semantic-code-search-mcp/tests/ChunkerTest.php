<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Tests;

use PHPUnit\Framework\TestCase;
use SemanticCodeSearchMcp\Index\Chunker;

final class ChunkerTest extends TestCase
{
    public function test_it_chunks_with_overlap_and_stable_ids(): void
    {
        $chunker = new Chunker(3, 1);
        $content = "one\ntwo\nthree\nfour\nfive\n";

        $chunks = $chunker->chunk('app/Example.php', $content);

        self::assertCount(3, $chunks);
        self::assertSame([1, 3, 5], array_column($chunks, 'start_line'));
        self::assertSame([3, 5, 5], array_column($chunks, 'end_line'));
        self::assertSame("one\ntwo\nthree", $chunks[0]['content']);
        self::assertSame($chunks, $chunker->chunk('app/Example.php', $content));
    }

    public function test_it_skips_blank_payloads(): void
    {
        $chunker = new Chunker(4, 2);

        self::assertSame([], $chunker->chunk('empty.txt', ''));
        self::assertSame([], $chunker->chunk('blank.txt', "\n\n"));
    }
}
