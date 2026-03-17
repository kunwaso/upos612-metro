<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp\Index;

final class Chunker
{
    private int $chunkLines;

    private int $chunkOverlap;

    public function __construct(int $chunkLines, int $chunkOverlap)
    {
        $this->chunkLines = max(1, $chunkLines);
        $this->chunkOverlap = min(max(0, $chunkOverlap), max(0, $this->chunkLines - 1));
    }

    /**
     * @return array<int, array{id: string, path: string, start_line: int, end_line: int, content: string}>
     */
    public function chunk(string $path, string $content): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $normalized);

        if ($normalized === '' || ($lines === [''])) {
            return [];
        }

        if (str_ends_with($normalized, "\n")) {
            array_pop($lines);
        }

        if ($lines === []) {
            return [];
        }

        $step = max(1, $this->chunkLines - $this->chunkOverlap);
        $chunks = [];

        for ($start = 1; $start <= count($lines); $start += $step) {
            $slice = array_slice($lines, $start - 1, $this->chunkLines);
            $text = implode("\n", $slice);

            if (trim($text) === '') {
                continue;
            }

            $end = $start + count($slice) - 1;
            $chunks[] = [
                'id' => sha1($path."\n".$start."\n".$end."\n".sha1($text)),
                'path' => $path,
                'start_line' => $start,
                'end_line' => $end,
                'content' => $text,
            ];
        }

        return $chunks;
    }
}
