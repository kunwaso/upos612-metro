#!/usr/bin/env node
'use strict';

const {
  readJsonInputFromStdin,
  redactSecrets,
  runAuditForUrl,
} = require('./audit-core');

function parseCliArguments(argv) {
  const args = Array.isArray(argv) ? argv.slice(2) : [];
  const output = {};

  if (args.length > 0 && !args[0].startsWith('--')) {
    output.url = args[0];
  }

  for (let index = 0; index < args.length; index += 1) {
    const argument = args[index];
    if (!argument.startsWith('--')) {
      continue;
    }

    const key = argument.replace(/^--/, '');
    const next = args[index + 1];
    if (!next || next.startsWith('--')) {
      output[key] = true;
      continue;
    }

    output[key] = next;
    index += 1;
  }

  return output;
}

async function main() {
  const cli = parseCliArguments(process.argv);
  const stdinInput = await readJsonInputFromStdin();
  const payload = { ...stdinInput };

  if (!payload.url && cli.url) {
    payload.url = cli.url;
  }

  if (payload.waitUntil === undefined && cli.waitUntil) {
    payload.waitUntil = cli.waitUntil;
  }

  if (payload.waitAfterLoadMs === undefined && cli.waitAfterLoadMs) {
    payload.waitAfterLoadMs = Number(cli.waitAfterLoadMs);
  }

  if (payload.timeoutMs === undefined && cli.timeoutMs) {
    payload.timeoutMs = Number(cli.timeoutMs);
  }

  if (payload.persist_report === undefined && cli.persist_report !== undefined) {
    payload.persist_report = cli.persist_report === true ? true : String(cli.persist_report).toLowerCase() !== 'false';
  }

  if (payload.report_dir === undefined && cli.report_dir) {
    payload.report_dir = cli.report_dir;
  }

  if (payload.report_slug === undefined && cli.report_slug) {
    payload.report_slug = cli.report_slug;
  }

  if (payload.saveStorageStatePath === undefined) {
    payload.saveStorageStatePath = payload.save_storage_state_path || cli.save_storage_state_path || null;
  }

  payload.mode = 'interactive';
  payload.headed = true;
  payload.persist_report = payload.persist_report !== false;
  payload.enableElementPick = payload.enableElementPick !== false;
  payload.manualPauseMessage =
    payload.manualPauseMessage ||
    'Interactive audit is paused. Login/interact now, double-click any element to pick it, then press Enter here to generate the report: ';

  const result = await runAuditForUrl(payload);
  const output = {
    ...result.payload,
    interactive: true,
  };

  process.stdout.write(`${JSON.stringify(output)}\n`);
  process.exit(result.exitCode);
}

main().catch((error) => {
  const runnerError = redactSecrets(error && error.message ? error.message : String(error));
  const payload = {
    auditStatus: 'fail',
    url: null,
    findings: [],
    runnerError,
    interactive: true,
  };
  process.stdout.write(`${JSON.stringify(payload)}\n`);
  process.exit(1);
});
