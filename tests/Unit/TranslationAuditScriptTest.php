<?php

namespace Tests\Unit;

use Tests\TestCase;

class TranslationAuditScriptTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'translation-audit-' . uniqid('', true);
        mkdir($this->tempRoot . '/lang/en', 0777, true);
        mkdir($this->tempRoot . '/lang/vi', 0777, true);
        mkdir($this->tempRoot . '/Modules/Foo/Resources/lang/en', 0777, true);
        mkdir($this->tempRoot . '/Modules/Foo/Resources/lang/vi', 0777, true);
        mkdir($this->tempRoot . '/lang/vendor/bar/en', 0777, true);

        require_once base_path('scripts/translation-audit.php');

        $this->writePhpArrayFile($this->tempRoot . '/lang/en/core.php', [
            'welcome' => 'Hello',
            'brand' => 'UPOS',
            'missing_key' => 'Missing in vi',
        ]);
        $this->writePhpArrayFile($this->tempRoot . '/lang/vi/core.php', [
            'welcome' => 'Xin chao',
            'brand' => 'UPOS',
        ]);

        $this->writePhpArrayFile($this->tempRoot . '/Modules/Foo/Resources/lang/en/module.php', [
            'title' => 'Title',
            'same' => 'Same text',
        ]);
        $this->writePhpArrayFile($this->tempRoot . '/Modules/Foo/Resources/lang/vi/module.php', [
            'title' => 'Tieu de',
            'same' => 'Same text',
        ]);

        $this->writePhpArrayFile($this->tempRoot . '/lang/vendor/bar/en/vendor.php', [
            'label' => 'Vendor label',
        ]);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempRoot)) {
            $this->deleteDirectory($this->tempRoot);
        }

        parent::tearDown();
    }

    public function test_it_detects_missing_files_keys_and_unchanged_values_with_allowlist(): void
    {
        $report = translation_audit_run($this->tempRoot, ['UPOS']);

        $this->assertSame(3, $report['summary']['roots_scanned']);
        $this->assertSame(3, $report['summary']['files_checked']);
        $this->assertSame(1, $report['summary']['missing_files']);
        $this->assertSame(1, $report['summary']['missing_keys']);
        $this->assertSame(1, $report['summary']['unchanged_values']);
        $this->assertSame(3, $report['summary']['issue_count']);

        $issueTypes = array_column($report['issues'], 'type');
        $this->assertContains('missing_file', $issueTypes);
        $this->assertContains('missing_key', $issueTypes);
        $this->assertContains('unchanged_value', $issueTypes);

        $missingFileIssue = $this->firstIssueOfType($report['issues'], 'missing_file');
        $this->assertSame('vendor:bar', $missingFileIssue['scope']);
        $this->assertSame('lang/vendor/bar/vi/vendor.php', str_replace('\\', '/', $missingFileIssue['file']));

        $missingKeyIssue = $this->firstIssueOfType($report['issues'], 'missing_key');
        $this->assertSame('core', $missingKeyIssue['scope']);
        $this->assertSame('missing_key', $missingKeyIssue['key']);

        $unchangedValueIssue = $this->firstIssueOfType($report['issues'], 'unchanged_value');
        $this->assertSame('module:Foo', $unchangedValueIssue['scope']);
        $this->assertSame('same', $unchangedValueIssue['key']);
        $this->assertSame('Same text', $unchangedValueIssue['value']);
    }

    private function writePhpArrayFile(string $path, array $data): void
    {
        file_put_contents($path, "<?php\n\nreturn " . var_export($data, true) . ";\n");
    }

    /**
     * @param array<int, array<string, mixed>> $issues
     * @return array<string, mixed>
     */
    private function firstIssueOfType(array $issues, string $type): array
    {
        foreach ($issues as $issue) {
            if (($issue['type'] ?? null) === $type) {
                return $issue;
            }
        }

        $this->fail('Expected an issue of type ' . $type . '.');
    }

    private function deleteDirectory(string $directory): void
    {
        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
