# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial release: append-only `audit_events` table, immutable `AuditEvent`
  model, fluent `audit()` builder, queued writes via `RecordAuditEvent`,
  `simple-audit:archive` retention command, and a configurable model/table/
  connection/queue via `config/simple-audit.php`.
- `AuditEvent` scopes (`ofType`, `forActor`, `forSubject`, `between`,
  `recent`) via the `#[Scope]` attribute (Laravel 12.4+), casts via a
  `casts()` method.
- Immutability enforced via `AuditEventObserver`, wired with `#[ObservedBy]`.
- Laravel Boost support: an AI guideline and an on-demand skill under
  `resources/boost/`, auto-loaded by consumers running `php artisan boost:install`.
- `examples/` folder: a starter `AuditEventType` enum and a subscriber
  wired to Laravel's built-in auth events, publishable via
  `php artisan vendor:publish --tag=simple-audit-examples`.
- Test suite via Orchestra Testbench; CI across PHP 8.2–8.4 and Laravel
  12.4+/13.
