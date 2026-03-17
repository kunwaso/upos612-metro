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

  if (payload.storage_state_path === undefined && cli.storage_state_path) {
    payload.storage_state_path = cli.storage_state_path;
  }

  if (payload.login_url === undefined && cli.login_url) {
    payload.login_url = cli.login_url;
  }

  if (payload.login_username === undefined && cli.login_username) {
    payload.login_username = cli.login_username;
  }

  if (payload.login_password === undefined && cli.login_password) {
    payload.login_password = cli.login_password;
  }

  const result = await runAuditForUrl(payload);
  process.stdout.write(`${JSON.stringify(result.payload)}\n`);
  process.exit(result.exitCode);
}

main().catch((error) => {
  const runnerError = redactSecrets(error && error.message ? error.message : String(error));
  const payload = {
    auditStatus: 'fail',
    url: null,
    findings: [],
    runnerError,
  };
  process.stdout.write(`${JSON.stringify(payload)}\n`);
  process.exit(1);
});
