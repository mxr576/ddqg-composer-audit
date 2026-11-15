# ddqg-composer-audit

A Composer plugin that adds advisories from [mxr576/ddqg](https://packagist.org/packages/mxr576/ddqg) (insecure, unsupported, deprecated, and non-Drupal-10-compatible package versions) to `composer audit`. Entry point: `src/Presentation/Composer/Plugin.php`, which registers one `ComposerAuditRepository` per finder.

## Runtime constraints

The plugin runs inside the user's Composer process, so:

- Code against the lowest versions `composer.json` allows: PHP syntax for the `php` constraint, Composer APIs for `composer/composer`. CI runs the e2e test with lowest dependencies.
- Build on what Composer already ships (`HttpDownloader`, `Semver`, `Platform`). Each new runtime dependency risks conflicting with the host project's dependencies.
- Send messages to STDERR with `$io->writeError()`, so `composer audit --format=json` keeps clean JSON on STDOUT.

## Architecture

`src/` uses layers: `Domain` → `Application` → `Infrastructure` / `Presentation`, plus `Supportive` for wiring (factories, adapters) that fits no layer. `phparkitect.php` holds the dependency rules, including the short list of Composer classes `Domain` may use.

Fix new violations in the code. Leave `phparkitect-baseline.json` and `phpstan-baseline.neon` as they are: both record existing debt.

## Static analysis

PHPStan checks exceptions: every method a `RuntimeException` (or subclass) can leave needs a `@throws` tag. `phpstan.neon.dist` lists the unchecked exceptions.

## Checks

A change is done when everything CI runs is green: the e2e test below, plus:

```shell
composer validate && composer normalize
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse --memory-limit=-1
vendor/bin/phparkitect check
vendor/bin/phpunit
```

When you add or change a check, edit the workflow and this section together. When you change a constraint in `composer.json`, follow `docs/agents/changing-constraints.md`.

`php-cs-fixer` rewrites `LICENSE.md` to the current year and adds the license header to PHP files. Keep those edits. When it refuses to start because the local PHP is newer than it supports, rerun it with `PHP_CS_FIXER_IGNORE_ENV=1`.

`vendor/bin/phpunit` tests the package finders through `PackageFinder`, asserting only on the returned advisories. When you add or change a test, follow `docs/agents/writing-tests.md`.

The e2e test is the acceptance test for Composer integration. It runs `composer audit` against a fixture project (`tests/fixtures/e2e`) and checks the JSON in `tests/fixtures/e2e/test.php`:

```shell
composer install -d tests/fixtures/composer_home && composer install -d tests/fixtures/e2e
composer reinstall -d tests/fixtures/composer_home mxr576/ddqg-composer-audit
tests/fixtures/e2e/test.php "$(DDQG_COMPOSER_AUDIT_TEST_ENV=1 COMPOSER_HOME=$PWD/tests/fixtures/composer_home ./vendor/bin/composer --no-ansi -d tests/fixtures/e2e audit --format=json)"
tests/fixtures/e2e/test.php "$(DDQG_COMPOSER_AUDIT_TEST_ENV=1 COMPOSER_HOME=$PWD/tests/fixtures/composer_home ./vendor/bin/composer --no-ansi -d tests/fixtures/e2e audit --locked --format=json)"
tests/fixtures/e2e/test.php "$(DDQG_COMPOSER_AUDIT_CHECK_D10_COMPATIBILITY=false DDQG_COMPOSER_AUDIT_TEST_ENV=1 COMPOSER_HOME=$PWD/tests/fixtures/composer_home ./vendor/bin/composer --no-ansi -d tests/fixtures/e2e audit --format=json)" no-d10
```

`tests/fixtures/composer_home` holds a mirrored copy of this plugin, and `composer install` leaves an existing copy as it is. Run the `reinstall` line after every `src/` change, or the e2e test runs stale plugin code.

`DDQG_COMPOSER_AUDIT_TEST_ENV=1` swaps the live DDQG fetch for the `*FromSnapshotForTesting` providers in `src/Infrastructure/Ddqg/`. A behaviour change that should show up in audit output needs those snapshots, the fixture's `composer.json`, and the assertions in `test.php` updated together.

## Feature lifecycle

The project deliberately offers few opt-outs; put new opt-out behaviour on Composer's own mechanisms. The `ignore-deprecated-versions` / `ignore-unsupported-versions` config is deprecated and will be removed in 2.0.0, because Composer's `audit.ignore` replaces it. The D10-compatibility check is seasonal and will be removed after Drupal 9 EOL.
