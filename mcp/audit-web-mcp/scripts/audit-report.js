'use strict';

const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const TRIAGE_CATEGORIES = ['frontend_js', 'backend_http', 'ui_render', 'auth', 'performance', 'unknown'];

function normalizePathForOutput(value) {
  return String(value).replace(/\\/g, '/');
}

function ensureDirectory(directoryPath) {
  fs.mkdirSync(directoryPath, { recursive: true });
  return directoryPath;
}

function slugifyPart(value, fallback = 'audit') {
  if (typeof value !== 'string' || value.trim() === '') {
    return fallback;
  }

  const slug = value
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 64);

  return slug || fallback;
}

function urlSlug(url) {
  if (typeof url !== 'string' || url.trim() === '') {
    return 'audit';
  }

  try {
    const parsed = new URL(url);
    return slugifyPart(`${parsed.hostname}${parsed.pathname}`, 'audit');
  } catch (_error) {
    return slugifyPart(url, 'audit');
  }
}

function sessionTimestamp(date = new Date()) {
  const year = String(date.getFullYear());
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  const seconds = String(date.getSeconds()).padStart(2, '0');

  return `${year}${month}${day}T${hours}${minutes}${seconds}`;
}

function createSessionId(url, reportSlug) {
  const slug = slugifyPart(reportSlug || urlSlug(url), 'audit');
  const random = crypto.randomBytes(3).toString('hex');

  return `${sessionTimestamp()}-${random}-${slug}`;
}

function isAuthSignal(text) {
  return /\b(auth|login|logout|unauth|unauthori[sz]ed|forbidden|csrf|session expired)\b/i.test(text);
}

function isPerformanceSignal(text) {
  return /\b(timeout|timed out|slow|networkidle|navigation timeout|took too long|err_timed_out)\b/i.test(text);
}

function classifyFinding(finding) {
  const kind = String(finding.kind || '').toLowerCase();
  const message = String(finding.message || '');
  const failureReason = String(finding.failureReason || '');
  const combined = `${message} ${failureReason}`.trim();
  const status = Number(finding.status || 0);

  if (kind === 'console_error' || kind === 'page_error' || kind === 'unhandled_rejection') {
    return 'frontend_js';
  }

  if (kind === 'bad_response' || kind === 'failed_request') {
    if (status === 401 || status === 403 || isAuthSignal(combined)) {
      return 'auth';
    }

    if (isPerformanceSignal(combined)) {
      return 'performance';
    }

    return 'backend_http';
  }

  if (kind === 'blank_screen') {
    return 'ui_render';
  }

  if (kind === 'interaction_failure') {
    if (status === 401 || status === 403 || isAuthSignal(combined)) {
      return 'auth';
    }

    if (isPerformanceSignal(combined)) {
      return 'performance';
    }

    if (/\b(selector|visible|click|fill|hover|element|detached|not found)\b/i.test(combined)) {
      return 'ui_render';
    }

    return 'unknown';
  }

  return 'unknown';
}

function annotateFindingsWithTriage(findings) {
  if (!Array.isArray(findings)) {
    return [];
  }

  return findings.map((finding) => {
    const triageCategory = TRIAGE_CATEGORIES.includes(finding.triageCategory)
      ? finding.triageCategory
      : classifyFinding(finding);

    return {
      ...finding,
      triageCategory,
    };
  });
}

function buildTriageSummary(findings, options = {}) {
  const triagedFindings = annotateFindingsWithTriage(findings);
  const counts = {
    frontend_js: 0,
    backend_http: 0,
    ui_render: 0,
    auth: 0,
    performance: 0,
    unknown: 0,
  };

  let errorCount = 0;
  let warningCount = 0;

  for (const finding of triagedFindings) {
    const category = TRIAGE_CATEGORIES.includes(finding.triageCategory) ? finding.triageCategory : 'unknown';
    counts[category] += 1;

    if (finding.severity === 'warning') {
      warningCount += 1;
    } else {
      errorCount += 1;
    }
  }

  let primaryCategory = 'unknown';
  if (triagedFindings.length > 0) {
    let primaryCount = -1;
    for (const category of TRIAGE_CATEGORIES) {
      if (counts[category] > primaryCount) {
        primaryCategory = category;
        primaryCount = counts[category];
      }
    }
  }

  const reasons = [];
  const shouldEscalateToDevtools =
    (options.interactive === true && triagedFindings.length === 0) ||
    counts.performance > 0 ||
    counts.unknown > 0 ||
    options.forceDevtools === true;

  if (options.interactive === true && triagedFindings.length === 0) {
    reasons.push('No structured findings were captured during the interactive audit.');
  }
  if (counts.performance > 0) {
    reasons.push('Timing-sensitive or slow-path behavior was detected.');
  }
  if (counts.unknown > 0) {
    reasons.push('One or more findings remain ambiguous and need live inspection.');
  }

  return {
    counts,
    findingCount: triagedFindings.length,
    errorCount,
    warningCount,
    primaryCategory,
    shouldEscalateToDevtools,
    recommendedNextTool: shouldEscalateToDevtools ? 'chrome-devtools' : 'playwright',
    reasons,
  };
}

function createReportContext(options = {}) {
  const artifactsRoot = ensureDirectory(options.artifactsDir || path.resolve(process.cwd(), 'output', 'playwright', 'audit-web-mcp'));
  const reportRoot = ensureDirectory(options.reportDir || path.join(artifactsRoot, 'reports'));
  const sessionId = createSessionId(options.url || '', options.reportSlug || null);
  const sessionDir = ensureDirectory(path.join(reportRoot, sessionId));

  return {
    sessionId,
    reportRoot,
    sessionDir,
    reportJsonPath: path.join(sessionDir, 'report.json'),
    reportMarkdownPath: path.join(sessionDir, 'report.md'),
    latestJsonPath: path.join(reportRoot, 'latest.json'),
    latestMarkdownPath: path.join(reportRoot, 'latest.md'),
  };
}

function renderFindingMarkdown(finding, index) {
  const parts = [];
  parts.push(`### ${index + 1}. ${finding.kind || 'finding'} (${finding.triageCategory || 'unknown'})`);
  parts.push(`- Severity: ${finding.severity || 'error'}`);
  if (finding.url) {
    parts.push(`- URL: ${finding.url}`);
  }
  if (finding.status) {
    parts.push(`- HTTP: ${finding.status}${finding.statusText ? ` ${finding.statusText}` : ''}`);
  }
  if (finding.message) {
    parts.push(`- Message: ${finding.message}`);
  }
  if (finding.file || finding.line || finding.column) {
    parts.push(`- Source: ${finding.file || '(unknown)'}:${finding.line || 0}:${finding.column || 0}`);
  }
  if (Array.isArray(finding.artifactRefs) && finding.artifactRefs.length > 0) {
    parts.push(`- Artifacts: ${finding.artifactRefs.join(', ')}`);
  }

  return parts.join('\n');
}

function renderMarkdownReport(report) {
  const lines = [];
  lines.push('# Interactive Web Audit Report');
  lines.push('');
  lines.push(`- Session: ${report.sessionId || 'n/a'}`);
  lines.push(`- URL: ${report.url || 'n/a'}`);
  lines.push(`- Status: ${report.auditStatus || 'fail'}`);
  lines.push(`- Mode: ${report.mode || 'headless'}`);
  lines.push(`- Interactive: ${report.interactive === true ? 'yes' : 'no'}`);
  if (report.savedStorageStatePath) {
    lines.push(`- Saved storage state: ${report.savedStorageStatePath}`);
  }
  lines.push('');
  lines.push('## Triage Summary');
  lines.push('');
  lines.push(`- Primary category: ${report.triageSummary?.primaryCategory || 'unknown'}`);
  lines.push(`- Findings: ${report.triageSummary?.findingCount || 0}`);
  lines.push(`- Errors: ${report.triageSummary?.errorCount || 0}`);
  lines.push(`- Warnings: ${report.triageSummary?.warningCount || 0}`);
  lines.push(`- Escalate to DevTools: ${report.triageSummary?.shouldEscalateToDevtools ? 'yes' : 'no'}`);
  lines.push(`- Recommended next tool: ${report.triageSummary?.recommendedNextTool || 'playwright'}`);

  if (Array.isArray(report.triageSummary?.reasons) && report.triageSummary.reasons.length > 0) {
    lines.push('');
    lines.push('### Escalation Reasons');
    lines.push('');
    for (const reason of report.triageSummary.reasons) {
      lines.push(`- ${reason}`);
    }
  }

  lines.push('');
  lines.push('## Findings');
  lines.push('');

  if (!Array.isArray(report.findings) || report.findings.length === 0) {
    lines.push('No findings were recorded.');
  } else {
    report.findings.forEach((finding, index) => {
      lines.push(renderFindingMarkdown(finding, index));
      lines.push('');
    });
  }

  if (report.runnerError) {
    lines.push('## Runner Error');
    lines.push('');
    lines.push(report.runnerError);
    lines.push('');
  }

  return `${lines.join('\n').trim()}\n`;
}

function persistAuditReport(payload, options = {}) {
  const triagedFindings = annotateFindingsWithTriage(payload.findings || []);
  const triageSummary = payload.triageSummary || buildTriageSummary(triagedFindings, {
    forceDevtools: options.forceDevtools === true,
  });

  const report = {
    ...payload,
    findings: triagedFindings,
    triageSummary,
    sessionId: options.sessionContext.sessionId,
    reportJsonPath: normalizePathForOutput(options.sessionContext.reportJsonPath),
    reportMarkdownPath: normalizePathForOutput(options.sessionContext.reportMarkdownPath),
  };

  fs.writeFileSync(options.sessionContext.reportJsonPath, `${JSON.stringify(report, null, 2)}\n`, 'utf8');
  fs.writeFileSync(options.sessionContext.reportMarkdownPath, renderMarkdownReport(report), 'utf8');
  fs.copyFileSync(options.sessionContext.reportJsonPath, options.sessionContext.latestJsonPath);
  fs.copyFileSync(options.sessionContext.reportMarkdownPath, options.sessionContext.latestMarkdownPath);

  return report;
}

module.exports = {
  TRIAGE_CATEGORIES,
  annotateFindingsWithTriage,
  buildTriageSummary,
  classifyFinding,
  createReportContext,
  createSessionId,
  normalizePathForOutput,
  persistAuditReport,
  renderMarkdownReport,
  slugifyPart,
};
