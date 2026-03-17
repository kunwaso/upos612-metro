<?php

declare(strict_types=1);

namespace AuditWebMcp\Tests;

use AuditWebMcp\AuditWebTool;
use PHPUnit\Framework\TestCase;

final class AuditWebToolTest extends TestCase
{
    public function testValidateInputSupportsInteractiveReportOptions(): void
    {
        $tool = new AuditWebTool('D:/workspace', 'D:/workspace/mcp/audit-web-mcp', 'node', 'php');

        $validated = $this->invokePrivate($tool, 'validateInput', [
            'https://example.com/dashboard',
            'single',
            null,
            null,
            null,
            null,
            null,
            null,
            120,
            'load',
            3200,
            'interactive',
            true,
            'output/playwright/audit-web-mcp/reports',
            'dashboard-home',
            'output/playwright/audit-web-mcp/reports/state.json',
        ]);

        self::assertSame('interactive', $validated['mode']);
        self::assertTrue($validated['persist_report']);
        self::assertSame('output/playwright/audit-web-mcp/reports', $validated['report_dir']);
        self::assertSame('dashboard-home', $validated['report_slug']);
        self::assertSame('output/playwright/audit-web-mcp/reports/state.json', $validated['save_storage_state_path']);
    }

    public function testValidateInputRejectsInteractivePrefixAudit(): void
    {
        $tool = new AuditWebTool('D:/workspace', 'D:/workspace/mcp/audit-web-mcp', 'node', 'php');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Interactive mode is only supported for single-URL audits.');

        $this->invokePrivate($tool, 'validateInput', [
            'https://example.com',
            'prefix',
            'dashboard',
            null,
            null,
            null,
            null,
            null,
            90,
            'networkidle',
            2500,
            'interactive',
            true,
            null,
            null,
            null,
        ]);
    }

    public function testNormalizeSingleResultIncludesReportMetadata(): void
    {
        $tool = new AuditWebTool('D:/workspace', 'D:/workspace/mcp/audit-web-mcp', 'node', 'php');

        $normalized = $this->invokePrivate($tool, 'normalizeSingleResult', [[
            'auditStatus' => 'fail',
            'url' => 'https://example.com/dashboard',
            'findings' => [['kind' => 'console_error', 'severity' => 'error', 'message' => 'Boom']],
            'sessionId' => '20260317T120000-abc123-dashboard',
            'reportJsonPath' => 'output/playwright/audit-web-mcp/reports/latest.json',
            'reportMarkdownPath' => 'output/playwright/audit-web-mcp/reports/latest.md',
            'savedStorageStatePath' => 'output/playwright/audit-web-mcp/reports/state.json',
            'triageSummary' => ['primaryCategory' => 'frontend_js'],
        ], 'https://example.com/dashboard']);

        self::assertSame('20260317T120000-abc123-dashboard', $normalized['sessionId']);
        self::assertSame('output/playwright/audit-web-mcp/reports/latest.json', $normalized['reportJsonPath']);
        self::assertSame('output/playwright/audit-web-mcp/reports/latest.md', $normalized['reportMarkdownPath']);
        self::assertSame('output/playwright/audit-web-mcp/reports/state.json', $normalized['savedStorageStatePath']);
        self::assertSame(['primaryCategory' => 'frontend_js'], $normalized['triageSummary']);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private function invokePrivate(object $target, string $method, array $arguments): mixed
    {
        $reflection = new \ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($target, $arguments);
    }
}
