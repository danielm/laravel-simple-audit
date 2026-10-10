# Security Policy

## Supported versions

Only the latest major version is actively supported with security fixes.

## Reporting a vulnerability

- A description of the vulnerability and its potential impact.
- Steps to reproduce, or a proof-of-concept if possible.
- Any suggested fix, if you have one.

## Scope notes specific to this package

- `metadata`/properties passed to `->log()` are stored as-is in the
  `audit_events` table. This package does not scrub or validate that
  content — it is the consuming application's responsibility to never pass
  secrets, tokens, or other sensitive values into it (see the README).
- The `simple-audit:archive` command writes exported data to whatever
  filesystem disk it's configured with; securing that disk (access
  control, encryption at rest) is the consuming application's
  responsibility.
