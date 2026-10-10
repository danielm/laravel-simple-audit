# Contributing

Contributions are welcome. A few guidelines to keep things consistent:

## Before you start

- Open an issue to discuss anything non-trivial (a new feature, a breaking
  change) before writing code, so effort isn't wasted on something that
  won't be merged.
- Bug fixes and small improvements can go straight to a pull request.

## Development setup

```bash
git clone <your fork>
cd laravel-simple-audit
composer install
```

## Before opening a pull request

```bash
composer test        # PHPUnit, via Orchestra Testbench
composer format       # Laravel Pint — fixes style issues in place
composer analyse       # Larastan / PHPStan
```

All three run in CI (`.github/workflows/tests.yml`) against the PHP ×
Laravel matrix described in the README, plus a non-blocking Pint check —
but running them locally first saves a round trip.

## Pull request guidelines

- One logical change per PR. Keep unrelated refactors out of a bugfix PR.
- Add or update tests for any behavior change — see the "Testing" section
  of the README for how the test suite is structured.
- Update the README and/or `examples/` if the change affects documented
  behavior or usage.
- Follow the existing code style (Pint enforces this automatically; run
  `composer format` before committing).

## Reporting bugs

Include:
- Package version, PHP version, Laravel version.
- A minimal reproduction (a failing test is ideal, but a code snippet is fine).
- What you expected vs. what happened.

## Code of conduct

This project follows Laravel's own [code of conduct](https://laravel.com/docs/contributions#code-of-conduct).
Be respectful and constructive.
