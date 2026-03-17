#!/usr/bin/env node
'use strict';

const {
  readJsonInputFromStdin,
  redactSecrets,
  runAuditBatch,
} = require('./audit-core');

function parseCliArguments(argv) {
  const args = Array.isArray(argv) ? argv.slice(2) : [];
  const output = {};

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
  const input = await readJsonInputFromStdin();

  if (input.concurrency === undefined && cli.concurrency) {
    input.concurrency = Number(cli.concurrency);
  }

  if (input.timeoutMs === undefined && cli.timeoutMs) {
    input.timeoutMs = Number(cli.timeoutMs);
  }

  if (input.waitUntil === undefined && cli.waitUntil) {
    input.waitUntil = cli.waitUntil;
  }

  if (input.waitAfterLoadMs === undefined && cli.waitAfterLoadMs) {
    input.waitAfterLoadMs = Number(cli.waitAfterLoadMs);
  }

  if (input.storage_state_path === undefined && cli.storage_state_path) {
    input.storage_state_path = cli.storage_state_path;
  }

  if (input.login_url === undefined && cli.login_url) {
    input.login_url = cli.login_url;
  }

  if (input.login_username === undefined && cli.login_username) {
    input.login_username = cli.login_username;
  }

  if (input.login_password === undefined && cli.login_password) {
    input.login_password = cli.login_password;
  }

  const result = await runAuditBatch(input);
  process.stdout.write(`${JSON.stringify(result.payload)}\n`);
  process.exit(result.exitCode);
}

main().catch((error) => {
  const payload = {
    runnerError: redactSecrets(error && error.message ? error.message : String(error)),
  };
  process.stdout.write(`${JSON.stringify(payload)}\n`);
  process.exit(1);
});
