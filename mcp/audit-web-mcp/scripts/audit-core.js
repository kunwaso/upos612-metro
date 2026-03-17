'use strict';

const crypto = require('crypto');
const fs = require('fs');
const path = require('path');
const readline = require('readline');
const { chromium } = require('@playwright/test');

const DEFAULT_CONSOLE_IGNORE = [
  /Browsing Topics API removed/i,
  /chext_/i,
  /injection-topics/i,
];

const DEFAULT_WAIT_UNTIL = 'networkidle';
const DEFAULT_WAIT_AFTER_LOAD_MS = 2500;
const DEFAULT_TIMEOUT_MS = 45000;
const DEFAULT_BATCH_CONCURRENCY = 3;
const MAX_SNIPPET_LENGTH = 500;
const DEFAULT_ARTIFACTS_DIR = path.resolve(process.cwd(), 'output', 'playwright', 'audit-web-mcp');
const SUPPORTED_WAIT_UNTIL = new Set(['domcontentloaded', 'load', 'networkidle', 'commit']);

function isObject(value) {
  return value !== null && typeof value === 'object' && !Array.isArray(value);
}

function normalizePathForOutput(value) {
  return String(value).replace(/\\/g, '/');
}

function ensureDirectory(directoryPath) {
  fs.mkdirSync(directoryPath, { recursive: true });
  return directoryPath;
}

function toIntegerOrDefault(value, defaultValue, min = Number.MIN_SAFE_INTEGER) {
  if (value === undefined || value === null || value === '') {
    return defaultValue;
  }

  const parsed = Number(value);
  if (!Number.isFinite(parsed)) {
    return defaultValue;
  }

  return Math.max(min, Math.trunc(parsed));
}

function isHttpUrl(url) {
  if (typeof url !== 'string' || url.trim() === '') {
    return false;
  }

  try {
    const parsed = new URL(url.trim());
    return parsed.protocol === 'http:' || parsed.protocol === 'https:';
  } catch (_error) {
    return false;
  }
}

function normalizeWaitUntil(value) {
  if (typeof value !== 'string') {
    return DEFAULT_WAIT_UNTIL;
  }

  const normalized = value.trim().toLowerCase();
  return SUPPORTED_WAIT_UNTIL.has(normalized) ? normalized : DEFAULT_WAIT_UNTIL;
}

function truncate(value, maxLength = MAX_SNIPPET_LENGTH) {
  if (typeof value !== 'string') {
    return value;
  }

  if (value.length <= maxLength) {
    return value;
  }

  return `${value.slice(0, maxLength)}...`;
}

function redactSecrets(value) {
  if (value === null || value === undefined) {
    return value;
  }

  let text = value;
  if (typeof text !== 'string') {
    try {
      text = JSON.stringify(text);
    } catch (_error) {
      text = String(text);
    }
  }

  let redacted = text;

  redacted = redacted.replace(/\b(Bearer)\s+[A-Za-z0-9\-._~+/]+=*/gi, '$1 [REDACTED]');
  redacted = redacted.replace(/\b(Authorization|Cookie|Set-Cookie)\s*:\s*([^\r\n]+)/gi, '$1: [REDACTED]');
  redacted = redacted.replace(
    /("?)(password|passwd|pwd|token|access_token|refresh_token|api[_-]?key|apikey|secret|csrf|xsrf|authorization|cookie)\1\s*[:=]\s*("?)([^"'\s,&}]+)/gi,
    (_match, _q1, key) => `${key}: [REDACTED]`
  );
  redacted = redacted.replace(
    /([?&](?:password|passwd|pwd|token|access_token|refresh_token|api[_-]?key|apikey|secret|csrf|xsrf|authorization|cookie)=)([^&\s]+)/gi,
    '$1[REDACTED]'
  );

  return redacted;
}

function toSnippet(value, maxLength = MAX_SNIPPET_LENGTH) {
  if (value === null || value === undefined) {
    return null;
  }

  const normalized = typeof value === 'string' ? value : redactSecrets(value);
  const sanitized = redactSecrets(normalized).replace(/\s+/g, ' ').trim();

  return truncate(sanitized, maxLength);
}

function findingFingerprint(input) {
  const source = {
    kind: input.kind || '',
    severity: input.severity || '',
    url: input.url || '',
    method: input.method || '',
    status: input.status || '',
    resourceType: input.resourceType || '',
    failureReason: input.failureReason || '',
    message: input.message || '',
    file: input.file || '',
    line: input.line || '',
    column: input.column || '',
  };

  return crypto.createHash('sha1').update(JSON.stringify(source)).digest('hex');
}

function createFinding(rawFinding) {
  const finding = { ...rawFinding };
  finding.kind = String(finding.kind || 'page_error');
  finding.severity = String(finding.severity || 'error');

  if (finding.message !== undefined && finding.message !== null) {
    finding.message = truncate(redactSecrets(String(finding.message)));
  }

  if (finding.stack !== undefined && finding.stack !== null) {
    finding.stack = truncate(redactSecrets(String(finding.stack)), 2000);
  }

  if (finding.requestBodySnippet !== undefined && finding.requestBodySnippet !== null) {
    finding.requestBodySnippet = toSnippet(finding.requestBodySnippet);
  }

  if (finding.responseBodySnippet !== undefined && finding.responseBodySnippet !== null) {
    finding.responseBodySnippet = toSnippet(finding.responseBodySnippet);
  }

  if (Array.isArray(finding.artifactRefs)) {
    finding.artifactRefs = Array.from(
      new Set(
        finding.artifactRefs
          .filter((item) => typeof item === 'string' && item.trim() !== '')
          .map((item) => normalizePathForOutput(item))
      )
    );
  }

  if (!finding.fingerprint || typeof finding.fingerprint !== 'string') {
    finding.fingerprint = findingFingerprint(finding);
  }

  return finding;
}

function mergeFindings(targetFindings, incomingFindings) {
  if (!Array.isArray(targetFindings) || !Array.isArray(incomingFindings)) {
    return;
  }

  const existing = new Set(targetFindings.map((finding) => finding.fingerprint));

  for (const rawFinding of incomingFindings) {
    const finding = createFinding(rawFinding);
    if (existing.has(finding.fingerprint)) {
      continue;
    }

    existing.add(finding.fingerprint);
    targetFindings.push(finding);
  }
}

function hasErrorFindings(findings) {
  return findings.some((finding) => finding.severity === 'error');
}

function attachArtifactRefs(findings, artifactRefs) {
  if (!Array.isArray(artifactRefs) || artifactRefs.length === 0) {
    return;
  }

  if (findings.length === 0) {
    return;
  }

  for (const finding of findings) {
    if (finding.severity !== 'error') {
      continue;
    }

    const merged = new Set([...(finding.artifactRefs || []), ...artifactRefs]);
    finding.artifactRefs = Array.from(merged);
  }
}

function extractStackLocation(stack) {
  if (typeof stack !== 'string' || stack.trim() === '') {
    return { file: null, line: null, column: null };
  }

  const patterns = [
    /\((.*):(\d+):(\d+)\)/,
    /at\s+(.*):(\d+):(\d+)/,
  ];

  for (const pattern of patterns) {
    const match = stack.match(pattern);
    if (!match) {
      continue;
    }

    return {
      file: match[1] || null,
      line: Number(match[2]) || null,
      column: Number(match[3]) || null,
    };
  }

  return { file: null, line: null, column: null };
}

function buildArtifactDirectory(baseDir, url) {
  const parsed = new URL(url);
  const slug = `${parsed.hostname}${parsed.pathname || ''}`
    .replace(/[^a-zA-Z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 80);
  const suffix = `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;

  return ensureDirectory(path.join(baseDir, `${slug || 'audit'}-${suffix}`));
}

async function readStdinRaw() {
  if (process.stdin.isTTY) {
    return '';
  }

  return new Promise((resolve, reject) => {
    let data = '';
    process.stdin.setEncoding('utf8');
    process.stdin.on('data', (chunk) => {
      data += chunk;
    });
    process.stdin.on('end', () => {
      resolve(data.trim());
    });
    process.stdin.on('error', reject);
  });
}

async function readJsonInputFromStdin() {
  const raw = await readStdinRaw();
  if (raw === '') {
    return {};
  }

  const parsed = JSON.parse(raw);
  if (!isObject(parsed)) {
    throw new Error('JSON input must be an object.');
  }

  return parsed;
}

async function waitForEnter(message) {
  if (!process.stdin.isTTY) {
    return;
  }

  await new Promise((resolve) => {
    const prompt = readline.createInterface({
      input: process.stdin,
      output: process.stderr,
    });

    prompt.question(message, () => {
      prompt.close();
      resolve();
    });
  });
}

async function installEarlyHooks(page) {
  await page.addInitScript(() => {
    if (window.__auditWebEarlyEventsInstalled) {
      return;
    }

    window.__auditWebEarlyEventsInstalled = true;
    window.__auditWebEarlyEvents = [];

    const pushEarlyEvent = (event) => {
      try {
        window.__auditWebEarlyEvents.push({ ...event, timestamp: Date.now() });
      } catch (_error) {
      }
    };

    window.addEventListener('error', (event) => {
      pushEarlyEvent({
        kind: 'window_error',
        message: event?.message || 'Window error',
        file: event?.filename || null,
        line: event?.lineno || null,
        column: event?.colno || null,
      });
    });

    window.addEventListener('unhandledrejection', (event) => {
      const reason = event?.reason;
      const message = reason && typeof reason === 'object' && 'message' in reason
        ? reason.message
        : String(reason || 'Unhandled promise rejection');

      pushEarlyEvent({
        kind: 'unhandled_rejection',
        message,
        stack: reason && reason.stack ? String(reason.stack) : null,
      });
    });

    if (typeof window.fetch === 'function') {
      const originalFetch = window.fetch.bind(window);
      window.fetch = async (...args) => {
        try {
          const response = await originalFetch(...args);
          if (!response.ok) {
            pushEarlyEvent({
              kind: 'fetch_bad_status',
              url: response.url || null,
              status: response.status || null,
              statusText: response.statusText || null,
            });
          }
          return response;
        } catch (error) {
          pushEarlyEvent({
            kind: 'fetch_failed',
            message: error?.message || String(error),
          });
          throw error;
        }
      };
    }

    const originalOpen = XMLHttpRequest.prototype.open;
    const originalSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function auditOpen(method, url) {
      this.__auditWebMethod = method;
      this.__auditWebUrl = url;
      return originalOpen.apply(this, arguments);
    };

    XMLHttpRequest.prototype.send = function auditSend(body) {
      this.addEventListener('error', () => {
        pushEarlyEvent({
          kind: 'xhr_failed',
          method: this.__auditWebMethod || null,
          url: this.__auditWebUrl || null,
          status: this.status || null,
        });
      });

      this.addEventListener('load', () => {
        if ((this.status || 0) >= 400) {
          pushEarlyEvent({
            kind: 'xhr_bad_status',
            method: this.__auditWebMethod || null,
            url: this.responseURL || this.__auditWebUrl || null,
            status: this.status || null,
            statusText: this.statusText || null,
          });
        }
      });

      return originalSend.call(this, body);
    };
  });
}

async function collectEarlyHookFindings(page, pageUrl) {
  const events = await page.evaluate(() => {
    if (!Array.isArray(window.__auditWebEarlyEvents)) {
      return [];
    }

    return window.__auditWebEarlyEvents.slice(-100);
  });

  const findings = [];
  for (const event of events) {
    const kind = event.kind || '';
    if (kind === 'window_error' || kind === 'unhandled_rejection') {
      findings.push(
        createFinding({
          kind: 'page_error',
          severity: 'error',
          url: pageUrl,
          message: event.message || 'Early browser error',
          stack: event.stack || null,
          file: event.file || null,
          line: event.line || null,
          column: event.column || null,
        })
      );
    } else if (kind === 'fetch_bad_status' || kind === 'xhr_bad_status') {
      findings.push(
        createFinding({
          kind: 'bad_response',
          severity: 'error',
          url: event.url || pageUrl,
          method: event.method || null,
          status: event.status || null,
          statusText: event.statusText || null,
          resourceType: kind.startsWith('fetch') ? 'fetch' : 'xhr',
          message: `Early ${kind.replace('_', ' ')} detected.`,
        })
      );
    } else if (kind === 'fetch_failed' || kind === 'xhr_failed') {
      findings.push(
        createFinding({
          kind: 'failed_request',
          severity: 'error',
          url: event.url || pageUrl,
          method: event.method || null,
          failureReason: event.message || 'Early request failure',
          resourceType: kind.startsWith('fetch') ? 'fetch' : 'xhr',
          message: `Early ${kind.replace('_', ' ')} detected.`,
        })
      );
    }
  }

  return findings;
}

async function installElementPicker(page) {
  await page.addInitScript(() => {
    if (window.__auditWebPickerInstalled) {
      return;
    }

    window.__auditWebPickerInstalled = true;
    window.__auditWebPickedElements = [];
    let highlighted = null;

    const selectorPath = (element) => {
      if (!element || !element.tagName) {
        return null;
      }

      if (element.id) {
        return `#${element.id}`;
      }

      const parts = [];
      let current = element;
      while (current && current.nodeType === Node.ELEMENT_NODE && parts.length < 8) {
        const tag = current.tagName.toLowerCase();
        let selector = tag;
        if (current.classList && current.classList.length) {
          selector += `.${Array.from(current.classList).slice(0, 2).join('.')}`;
        }

        const parent = current.parentElement;
        if (parent) {
          const siblings = Array.from(parent.children).filter((child) => child.tagName === current.tagName);
          if (siblings.length > 1) {
            selector += `:nth-of-type(${siblings.indexOf(current) + 1})`;
          }
        }

        parts.unshift(selector);
        current = parent;
      }

      return parts.join(' > ');
    };

    const clearHighlight = () => {
      if (highlighted) {
        highlighted.style.outline = highlighted.__auditWebOriginalOutline || '';
        delete highlighted.__auditWebOriginalOutline;
      }
    };

    document.addEventListener('mousemove', (event) => {
      const target = event.target;
      if (!target || !(target instanceof Element)) {
        return;
      }

      if (highlighted === target) {
        return;
      }

      clearHighlight();
      highlighted = target;
      highlighted.__auditWebOriginalOutline = highlighted.style.outline;
      highlighted.style.outline = '2px dashed #0d6efd';
    }, true);

    document.addEventListener('dblclick', (event) => {
      const target = event.target;
      if (!target || !(target instanceof Element)) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();

      const text = (target.innerText || target.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 120);
      window.__auditWebPickedElements.push({
        selector: selectorPath(target),
        tag: target.tagName.toLowerCase(),
        id: target.id || null,
        classes: Array.from(target.classList || []).slice(0, 6),
        text,
      });

      target.style.outline = '2px solid #198754';
      setTimeout(() => {
        if (target.style.outline === '2px solid rgb(25, 135, 84)' || target.style.outline === '2px solid #198754') {
          target.style.outline = target.__auditWebOriginalOutline || '';
        }
      }, 700);
    }, true);
  });
}

async function collectPickedElementFindings(page, pageUrl) {
  const picked = await page.evaluate(() => {
    if (!Array.isArray(window.__auditWebPickedElements)) {
      return [];
    }

    return window.__auditWebPickedElements.slice(-20);
  });

  return picked.map((selection) =>
    createFinding({
      kind: 'picked_element',
      severity: 'warning',
      url: pageUrl,
      message: `Picked ${selection.tag || 'element'} ${selection.selector || '(no selector)'}`,
      responseBodySnippet: selection,
    })
  );
}

async function firstVisibleSelector(page, selectors, timeoutMs) {
  for (const selector of selectors) {
    const locator = page.locator(selector).first();
    try {
      if (await locator.isVisible({ timeout: timeoutMs })) {
        return selector;
      }
    } catch (_error) {
    }
  }

  return null;
}

async function fillFirstSelector(page, selectors, value, timeoutMs) {
  const selector = await firstVisibleSelector(page, selectors, timeoutMs);
  if (!selector) {
    return null;
  }

  await page.fill(selector, String(value));
  return selector;
}

async function clickFirstSelector(page, selectors, timeoutMs) {
  const selector = await firstVisibleSelector(page, selectors, timeoutMs);
  if (!selector) {
    return null;
  }

  await page.click(selector);
  return selector;
}

async function performCredentialLogin(browser, options) {
  const loginUrl = options.login_url;
  const loginUsername = options.login_username;
  const loginPassword = options.login_password;

  if (!isHttpUrl(loginUrl)) {
    throw new Error('login_url must be a valid http(s) URL when credential login is used.');
  }

  if (typeof loginUsername !== 'string' || loginUsername.trim() === '') {
    throw new Error('login_username is required when credential login is used.');
  }

  if (typeof loginPassword !== 'string' || loginPassword === '') {
    throw new Error('login_password is required when credential login is used.');
  }

  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();

  try {
    await page.goto(loginUrl, {
      waitUntil: normalizeWaitUntil(options.waitUntil || 'load'),
      timeout: toIntegerOrDefault(options.timeoutMs, DEFAULT_TIMEOUT_MS, 1),
    });

    const userSelector = await fillFirstSelector(
      page,
      [
        'input[name="username"]',
        'input[name="email"]',
        'input[type="email"]',
        'input[id*="user"]',
        'input[name*="user"]',
        'input[id*="email"]',
      ],
      loginUsername,
      2000
    );

    const passwordSelector = await fillFirstSelector(
      page,
      [
        'input[name="password"]',
        'input[type="password"]',
        'input[id*="password"]',
      ],
      loginPassword,
      2000
    );

    if (!userSelector || !passwordSelector) {
      throw new Error('Could not find login form username/password fields.');
    }

    const submitSelector = await clickFirstSelector(
      page,
      [
        'button[type="submit"]',
        'input[type="submit"]',
        '[data-kt-login-action="submit"]',
        '.btn[type="submit"]',
      ],
      1200
    );

    if (!submitSelector) {
      await page.press(passwordSelector, 'Enter');
    }

    await page.waitForLoadState('networkidle', { timeout: toIntegerOrDefault(options.timeoutMs, DEFAULT_TIMEOUT_MS, 1) });
    await page.waitForTimeout(800);

    return await context.storageState();
  } finally {
    await context.close();
  }
}

async function resolveStorageState(browser, options) {
  if (options.storageState !== undefined && options.storageState !== null) {
    return options.storageState;
  }

  if (typeof options.storage_state_path === 'string' && options.storage_state_path.trim() !== '') {
    const absolutePath = path.isAbsolute(options.storage_state_path)
      ? options.storage_state_path
      : path.resolve(process.cwd(), options.storage_state_path);

    if (!fs.existsSync(absolutePath)) {
      throw new Error(`storage_state_path does not exist: ${options.storage_state_path}`);
    }

    return absolutePath;
  }

  const hasAnyCredentialField =
    (typeof options.login_url === 'string' && options.login_url.trim() !== '') ||
    (typeof options.login_username === 'string' && options.login_username.trim() !== '') ||
    (typeof options.login_password === 'string' && options.login_password !== '');

  if (!hasAnyCredentialField) {
    return null;
  }

  return await performCredentialLogin(browser, options);
}

async function detectBlankScreenFinding(page, pageUrl) {
  const heuristic = await page.evaluate(() => {
    const rootSelectors = ['#kt_app_root', '#app', '#root', '#kt_content', 'main', 'body'];
    const spinnerSelectors = [
      '.spinner-border',
      '.spinner-grow',
      '.spinner',
      '.loader',
      '.loading',
      '[data-kt-indicator="on"]',
      '.fa-spinner',
      '.fas.fa-spinner',
    ];

    const root = rootSelectors.map((selector) => document.querySelector(selector)).find(Boolean) || document.body;
    const bodyText = (document.body?.innerText || '').replace(/\s+/g, ' ').trim();
    const meaningfulLength = bodyText.replace(/[^a-zA-Z0-9]/g, '').length;
    const spinnerCount = spinnerSelectors.reduce((total, selector) => total + document.querySelectorAll(selector).length, 0);
    const rootIsEmpty = !!root && root.children.length === 0 && meaningfulLength < 16;
    const spinnerOnly = spinnerCount > 0 && meaningfulLength < 16;
    const almostNoText = meaningfulLength < 8;

    return {
      rootIsEmpty,
      spinnerOnly,
      almostNoText,
      spinnerCount,
      meaningfulLength,
    };
  });

  if (!heuristic.rootIsEmpty && !heuristic.spinnerOnly && !heuristic.almostNoText) {
    return null;
  }

  const reasons = [];
  if (heuristic.rootIsEmpty) {
    reasons.push('root container appears empty');
  }
  if (heuristic.spinnerOnly) {
    reasons.push('spinner visible with no meaningful content');
  }
  if (heuristic.almostNoText) {
    reasons.push('visible body text is nearly empty');
  }

  return createFinding({
    kind: 'blank_screen',
    severity: 'error',
    url: pageUrl,
    message: `Blank-screen heuristic triggered: ${reasons.join('; ') || 'unknown reason'}.`,
    responseBodySnippet: heuristic,
  });
}

async function collectBadResponseFinding(response) {
  const request = response.request();
  const resourceType = request.resourceType();
  if (resourceType !== 'xhr' && resourceType !== 'fetch') {
    return null;
  }

  const status = response.status();
  if (status < 400) {
    return null;
  }

  const method = request.method();
  const requestBody =
    method === 'POST' || method === 'PUT' || method === 'PATCH'
      ? request.postData() || null
      : null;

  let responseBody = null;
  try {
    responseBody = await response.text();
  } catch (_error) {
    responseBody = null;
  }

  let timing = null;
  try {
    timing = request.timing();
  } catch (_error) {
    timing = null;
  }

  let size = null;
  try {
    if (typeof request.sizes === 'function') {
      size = await request.sizes();
    }
  } catch (_error) {
    size = null;
  }

  if (!size) {
    const contentLength = response.headers()['content-length'];
    if (contentLength) {
      size = {
        responseBodySize: Number(contentLength) || null,
      };
    }
  }

  return createFinding({
    kind: 'bad_response',
    severity: 'error',
    url: response.url(),
    method,
    status,
    statusText: response.statusText(),
    resourceType,
    requestBodySnippet: requestBody,
    responseBodySnippet: responseBody,
    timing: timing || null,
    size: size || null,
    message: `XHR/fetch returned ${status} ${response.statusText()}.`,
  });
}

async function executeSteps(page, steps, findings, pageUrl) {
  if (!Array.isArray(steps) || steps.length === 0) {
    return;
  }

  for (let index = 0; index < steps.length; index += 1) {
    const step = steps[index];
    if (!isObject(step)) {
      mergeFindings(findings, [
        {
          kind: 'page_error',
          severity: 'error',
          url: pageUrl,
          message: `Step ${index + 1} is not an object.`,
        },
      ]);
      continue;
    }

    const action = String(step.action || step.type || '').trim().toLowerCase();
    if (action === '') {
      mergeFindings(findings, [
        {
          kind: 'page_error',
          severity: 'error',
          url: pageUrl,
          message: `Step ${index + 1} is missing an action.`,
        },
      ]);
      continue;
    }

    try {
      if (action === 'click' || action === 'dblclick') {
        if (typeof step.selector !== 'string' || step.selector.trim() === '') {
          throw new Error('selector is required.');
        }

        await page.click(step.selector, {
          button: 'left',
          clickCount: action === 'dblclick' ? 2 : 1,
          timeout: toIntegerOrDefault(step.timeoutMs, 5000, 1),
        });
      } else if (action === 'fill' || action === 'type') {
        if (typeof step.selector !== 'string' || step.selector.trim() === '') {
          throw new Error('selector is required.');
        }

        const value = step.value ?? step.text ?? '';
        if (action === 'fill') {
          await page.fill(step.selector, String(value), {
            timeout: toIntegerOrDefault(step.timeoutMs, 5000, 1),
          });
        } else {
          if (step.clear === true) {
            await page.fill(step.selector, '', {
              timeout: toIntegerOrDefault(step.timeoutMs, 5000, 1),
            });
          }
          await page.type(step.selector, String(value), {
            timeout: toIntegerOrDefault(step.timeoutMs, 5000, 1),
            delay: toIntegerOrDefault(step.delayMs, 30, 0),
          });
        }
      } else if (action === 'press') {
        const key = typeof step.key === 'string' && step.key.trim() !== '' ? step.key : 'Enter';
        if (typeof step.selector === 'string' && step.selector.trim() !== '') {
          await page.press(step.selector, key, {
            timeout: toIntegerOrDefault(step.timeoutMs, 5000, 1),
          });
        } else {
          await page.keyboard.press(key);
        }
      } else if (action === 'wait' || action === 'waitfortimeout') {
        await page.waitForTimeout(toIntegerOrDefault(step.ms, 500, 0));
      } else if (action === 'waitforselector') {
        if (typeof step.selector !== 'string' || step.selector.trim() === '') {
          throw new Error('selector is required.');
        }

        await page.waitForSelector(step.selector, {
          state: typeof step.state === 'string' ? step.state : 'visible',
          timeout: toIntegerOrDefault(step.timeoutMs, 8000, 1),
        });
      } else if (action === 'goto') {
        if (!isHttpUrl(step.url)) {
          throw new Error('url must be a valid http(s) URL.');
        }

        await page.goto(step.url, {
          waitUntil: normalizeWaitUntil(step.waitUntil),
          timeout: toIntegerOrDefault(step.timeoutMs, DEFAULT_TIMEOUT_MS, 1),
        });
      } else if (action === 'select') {
        if (typeof step.selector !== 'string' || step.selector.trim() === '') {
          throw new Error('selector is required.');
        }

        const values = Array.isArray(step.values) ? step.values : [step.value];
        await page.selectOption(step.selector, values.map((value) => String(value)));
      } else if (action === 'check') {
        if (typeof step.selector !== 'string' || step.selector.trim() === '') {
          throw new Error('selector is required.');
        }
        await page.check(step.selector);
      } else if (action === 'uncheck') {
        if (typeof step.selector !== 'string' || step.selector.trim() === '') {
          throw new Error('selector is required.');
        }
        await page.uncheck(step.selector);
      } else if (action === 'hover') {
        if (typeof step.selector !== 'string' || step.selector.trim() === '') {
          throw new Error('selector is required.');
        }
        await page.hover(step.selector);
      } else {
        throw new Error(`Unsupported step action "${action}".`);
      }
    } catch (error) {
      mergeFindings(findings, [
        {
          kind: 'page_error',
          severity: 'error',
          url: pageUrl,
          message: `Step ${index + 1} failed (${action}): ${error.message}`,
        },
      ]);

      if (step.stopOnFailure === true) {
        break;
      }
    }
  }
}

async function runAuditForUrl(rawOptions) {
  const options = {
    headed: false,
    verbose: false,
    waitUntil: DEFAULT_WAIT_UNTIL,
    waitAfterLoadMs: DEFAULT_WAIT_AFTER_LOAD_MS,
    timeoutMs: DEFAULT_TIMEOUT_MS,
    artifactsDir: DEFAULT_ARTIFACTS_DIR,
    ignoreConsole: DEFAULT_CONSOLE_IGNORE,
    softFailRunnerError: false,
    ...rawOptions,
  };

  const targetUrl = typeof options.url === 'string' ? options.url.trim() : '';
  if (!isHttpUrl(targetUrl)) {
    return {
      exitCode: 1,
      payload: {
        auditStatus: 'fail',
        url: targetUrl || null,
        findings: [],
        runnerError: 'url must be a valid http(s) URL.',
      },
    };
  }

  let browser = options.browser || null;
  let ownsBrowser = false;
  let context = null;
  let page = null;
  let fatalError = null;
  let crashDetected = false;
  let savedStorageStatePath = null;

  const findings = [];
  const artifactRefs = [];
  const asyncTasks = [];
  const artifactsRoot = ensureDirectory(options.artifactsDir || DEFAULT_ARTIFACTS_DIR);
  const artifactDirectory = buildArtifactDirectory(artifactsRoot, targetUrl);

  try {
    if (!browser) {
      const launchOptions = { headless: !options.headed };
      if (options.headed) {
        launchOptions.args = ['--disable-gpu', '--disable-dev-shm-usage', '--no-first-run'];
      }
      browser = await chromium.launch(launchOptions);
      ownsBrowser = true;
    }

    const storageState = await resolveStorageState(browser, options);
    const contextOptions = {
      ignoreHTTPSErrors: true,
    };
    if (storageState) {
      contextOptions.storageState = storageState;
    }

    context = await browser.newContext(contextOptions);
    await context.tracing.start({
      screenshots: true,
      snapshots: true,
      sources: true,
    });

    page = await context.newPage();
    page.setDefaultTimeout(toIntegerOrDefault(options.timeoutMs, DEFAULT_TIMEOUT_MS, 1));
    page.setDefaultNavigationTimeout(toIntegerOrDefault(options.timeoutMs, DEFAULT_TIMEOUT_MS, 1));

    if (options.enableElementPick === true) {
      await installElementPicker(page);
    }

    await installEarlyHooks(page);

    page.on('crash', () => {
      crashDetected = true;
      mergeFindings(findings, [
        {
          kind: 'page_error',
          severity: 'error',
          url: targetUrl,
          message: 'Playwright detected a page crash.',
        },
      ]);
    });

    page.on('console', (message) => {
      if (message.type() !== 'error') {
        return;
      }

      const text = message.text() || '';
      if (options.ignoreConsole.some((pattern) => pattern.test(text))) {
        return;
      }

      const location = message.location();
      mergeFindings(findings, [
        {
          kind: 'console_error',
          severity: 'error',
          url: targetUrl,
          message: text,
          file: location.url || null,
          line: location.lineNumber ? location.lineNumber + 1 : null,
          column: location.columnNumber ? location.columnNumber + 1 : null,
        },
      ]);
    });

    page.on('pageerror', (error) => {
      const stack = typeof error.stack === 'string' ? error.stack : null;
      const location = extractStackLocation(stack || '');
      mergeFindings(findings, [
        {
          kind: 'page_error',
          severity: 'error',
          url: targetUrl,
          message: error.message || 'Unhandled page error',
          stack,
          file: location.file,
          line: location.line,
          column: location.column,
        },
      ]);
    });

    page.on('requestfailed', (request) => {
      const failure = request.failure();
      mergeFindings(findings, [
        {
          kind: 'failed_request',
          severity: 'error',
          url: request.url(),
          method: request.method(),
          resourceType: request.resourceType(),
          failureReason: failure && failure.errorText ? failure.errorText : 'Request failed',
          message: `Request failed: ${failure && failure.errorText ? failure.errorText : 'unknown reason'}`,
        },
      ]);
    });

    page.on('response', (response) => {
      const task = (async () => {
        const finding = await collectBadResponseFinding(response);
        if (!finding) {
          return;
        }
        mergeFindings(findings, [finding]);
      })();

      asyncTasks.push(task);
    });

    await page.goto(targetUrl, {
      waitUntil: normalizeWaitUntil(options.waitUntil),
      timeout: toIntegerOrDefault(options.timeoutMs, DEFAULT_TIMEOUT_MS, 1),
    });

    const waitAfterLoadMs = toIntegerOrDefault(options.waitAfterLoadMs, DEFAULT_WAIT_AFTER_LOAD_MS, 0);
    if (waitAfterLoadMs > 0) {
      await page.waitForTimeout(waitAfterLoadMs);
    }

    if (Array.isArray(options.steps) && options.steps.length > 0) {
      await executeSteps(page, options.steps, findings, targetUrl);
    }

    if (typeof options.manualPauseMessage === 'string' && options.manualPauseMessage.trim() !== '') {
      await waitForEnter(options.manualPauseMessage);
      await page.waitForTimeout(600);
    }

    await Promise.allSettled(asyncTasks);

    const earlyFindings = await collectEarlyHookFindings(page, targetUrl);
    mergeFindings(findings, earlyFindings);

    if (options.enableElementPick === true) {
      const pickedElementFindings = await collectPickedElementFindings(page, targetUrl);
      mergeFindings(findings, pickedElementFindings);
    }

    const blankScreenFinding = await detectBlankScreenFinding(page, targetUrl);
    if (blankScreenFinding) {
      mergeFindings(findings, [blankScreenFinding]);
    }

    if (typeof options.saveStorageStatePath === 'string' && options.saveStorageStatePath.trim() !== '') {
      const absolutePath = path.isAbsolute(options.saveStorageStatePath)
        ? options.saveStorageStatePath
        : path.resolve(process.cwd(), options.saveStorageStatePath);
      ensureDirectory(path.dirname(absolutePath));
      await context.storageState({ path: absolutePath });
      savedStorageStatePath = normalizePathForOutput(absolutePath);
    }
  } catch (error) {
    fatalError = error;
  } finally {
    const shouldCaptureArtifacts = fatalError !== null || crashDetected || hasErrorFindings(findings);

    if (context) {
      if (shouldCaptureArtifacts && page && !page.isClosed()) {
        const screenshotPath = path.join(artifactDirectory, 'failure.png');
        try {
          await page.screenshot({
            path: screenshotPath,
            fullPage: true,
          });
          artifactRefs.push(normalizePathForOutput(screenshotPath));
        } catch (_error) {
        }
      }

      try {
        if (shouldCaptureArtifacts) {
          const tracePath = path.join(artifactDirectory, 'trace.zip');
          await context.tracing.stop({ path: tracePath });
          artifactRefs.push(normalizePathForOutput(tracePath));
        } else {
          await context.tracing.stop();
        }
      } catch (_error) {
      }

      try {
        await context.close();
      } catch (_error) {
      }
    }

    if (ownsBrowser && browser) {
      try {
        await browser.close();
      } catch (_error) {
      }
    }
  }

  attachArtifactRefs(findings, artifactRefs);

  if (fatalError) {
    const runnerErrorMessage = redactSecrets(fatalError.message || String(fatalError));

    if (options.softFailRunnerError === true || findings.length > 0) {
      mergeFindings(findings, [
        {
          kind: 'page_error',
          severity: 'error',
          url: targetUrl,
          message: `Runner error: ${runnerErrorMessage}`,
          artifactRefs,
        },
      ]);

      return {
        exitCode: 0,
        payload: {
          auditStatus: 'fail',
          url: targetUrl,
          findings,
          savedStorageStatePath,
        },
      };
    }

    return {
      exitCode: 1,
      payload: {
        auditStatus: 'fail',
        url: targetUrl,
        findings,
        runnerError: runnerErrorMessage,
        savedStorageStatePath,
      },
    };
  }

  const auditStatus = hasErrorFindings(findings) ? 'fail' : 'pass';

  return {
    exitCode: 0,
    payload: {
      auditStatus,
      url: targetUrl,
      findings,
      savedStorageStatePath,
    },
  };
}

async function runWithConcurrency(items, concurrency, worker) {
  const workItems = Array.isArray(items) ? items : [];
  const maxConcurrency = Math.max(1, Math.trunc(concurrency));
  let cursor = 0;

  async function runWorker() {
    while (cursor < workItems.length) {
      const currentIndex = cursor;
      cursor += 1;
      await worker(workItems[currentIndex], currentIndex);
    }
  }

  const workers = [];
  for (let index = 0; index < Math.min(maxConcurrency, workItems.length); index += 1) {
    workers.push(runWorker());
  }

  await Promise.all(workers);
}

async function runAuditBatch(rawOptions) {
  const options = {
    headed: false,
    waitUntil: DEFAULT_WAIT_UNTIL,
    waitAfterLoadMs: DEFAULT_WAIT_AFTER_LOAD_MS,
    timeoutMs: DEFAULT_TIMEOUT_MS,
    artifactsDir: DEFAULT_ARTIFACTS_DIR,
    concurrency: DEFAULT_BATCH_CONCURRENCY,
    ...rawOptions,
  };

  if (!Array.isArray(options.urls) || options.urls.length === 0) {
    return {
      exitCode: 1,
      payload: {
        runnerError: 'urls must be a non-empty array.',
      },
    };
  }

  for (const url of options.urls) {
    if (!isHttpUrl(url)) {
      return {
        exitCode: 1,
        payload: {
          runnerError: `Invalid URL in urls array: ${url}`,
        },
      };
    }
  }

  let browser = null;
  try {
    browser = await chromium.launch({ headless: !options.headed });
    const sharedStorageState = await resolveStorageState(browser, options);
    const results = new Array(options.urls.length);

    await runWithConcurrency(options.urls, toIntegerOrDefault(options.concurrency, DEFAULT_BATCH_CONCURRENCY, 1), async (url, index) => {
      const result = await runAuditForUrl({
        ...options,
        url,
        browser,
        storageState: sharedStorageState,
        softFailRunnerError: true,
      });

      results[index] = result.payload;
    });

    return {
      exitCode: 0,
      payload: results,
    };
  } catch (error) {
    return {
      exitCode: 1,
      payload: {
        runnerError: redactSecrets(error.message || String(error)),
      },
    };
  } finally {
    if (browser) {
      try {
        await browser.close();
      } catch (_error) {
      }
    }
  }
}

module.exports = {
  DEFAULT_BATCH_CONCURRENCY,
  DEFAULT_CONSOLE_IGNORE,
  DEFAULT_TIMEOUT_MS,
  DEFAULT_WAIT_AFTER_LOAD_MS,
  DEFAULT_WAIT_UNTIL,
  createFinding,
  isHttpUrl,
  mergeFindings,
  normalizePathForOutput,
  readJsonInputFromStdin,
  redactSecrets,
  runAuditBatch,
  runAuditForUrl,
  toIntegerOrDefault,
  toSnippet,
  waitForEnter,
};
