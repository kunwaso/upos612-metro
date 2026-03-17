'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('fs');
const os = require('os');
const path = require('path');

const {
  annotateFindingsWithTriage,
  buildTriageSummary,
  createReportContext,
  persistAuditReport,
} = require('../scripts/audit-report');

test('annotateFindingsWithTriage classifies known finding types', () => {
  const findings = annotateFindingsWithTriage([
    { kind: 'console_error', severity: 'error', message: 'ReferenceError: foo is not defined' },
    { kind: 'bad_response', severity: 'error', status: 500, message: 'XHR/fetch returned 500 Internal Server Error.' },
    { kind: 'blank_screen', severity: 'error', message: 'Blank-screen heuristic triggered.' },
    { kind: 'interaction_failure', severity: 'error', message: 'Step 2 failed (waitforselector): Timeout 5000ms exceeded.' },
  ]);

  assert.deepEqual(
    findings.map((finding) => finding.triageCategory),
    ['frontend_js', 'backend_http', 'ui_render', 'performance']
  );
});

test('buildTriageSummary recommends DevTools when findings are ambiguous or timing-sensitive', () => {
  const summary = buildTriageSummary([
    { kind: 'interaction_failure', severity: 'error', message: 'Step 1 failed (click): Timeout 5000ms exceeded.' },
    { kind: 'picked_element', severity: 'warning', message: 'Picked button' },
  ]);

  assert.equal(summary.shouldEscalateToDevtools, true);
  assert.equal(summary.recommendedNextTool, 'chrome-devtools');
  assert.equal(summary.primaryCategory, 'performance');
});

test('persistAuditReport writes json and markdown reports plus latest copies', () => {
  const tempRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'audit-web-mcp-'));
  const sessionContext = createReportContext({
    url: 'https://example.com/dashboard',
    artifactsDir: tempRoot,
    reportSlug: 'dashboard-audit',
  });

  const report = persistAuditReport(
    {
      auditStatus: 'fail',
      url: 'https://example.com/dashboard',
      mode: 'interactive',
      interactive: true,
      findings: [{ kind: 'console_error', severity: 'error', message: 'Boom' }],
    },
    { sessionContext }
  );

  assert.equal(fs.existsSync(sessionContext.reportJsonPath), true);
  assert.equal(fs.existsSync(sessionContext.reportMarkdownPath), true);
  assert.equal(fs.existsSync(sessionContext.latestJsonPath), true);
  assert.equal(fs.existsSync(sessionContext.latestMarkdownPath), true);
  assert.equal(report.sessionId, sessionContext.sessionId);
  assert.equal(report.triageSummary.primaryCategory, 'frontend_js');
});
