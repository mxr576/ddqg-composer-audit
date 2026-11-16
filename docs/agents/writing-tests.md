# Writing tests

Apply every rule below to each test you add or change.

## Seams

The suite has three seams. Test each rule once, at the lowest seam that owns it:

- **Package finder** (`FindPackagesByDdqgCategory`, one class for every DDQG category), covered by PHPUnit. Call the finder with the map Composer would pass (package name to an exact version, or an OR of exact versions) and assert on the findings it returns. Matching, naming the offending candidate, the finding's ID and title, ignore rules and the package metadata lookup are covered through it.
- **Advisory repository** (`ComposerAuditRepository`), covered by PHPUnit. Call `getSecurityAdvisories()` and assert on the Composer advisories it returns. Delivery details live here: the title attribution, the project link and the report time.
- **Composer to plugin**, covered by the e2e test (see `AGENTS.md`). It is the acceptance test for Composer integration.

A rule shared by all categories (for example range matching) lives in the one finder implementation, so it gets one test, through any category. Per-category behaviour (finding ID, title) gets a test per DDQG category, and ignore rules get one for each category that has them: `FindPackagesByDdqgCategoryTest` uses a data provider with one row per category, so covering a new category means adding one row.

## What earns a test

Every test must be able to fail for a plausible bug in observable behaviour through a public interface, and survive internal refactors. Domain behaviour, boundary invariants, transformations, parsers/serializers, data integrity and real bug regressions earn tests. Pure DTOs, `readonly` value objects, domain events, constants/config and pass-through glue are covered through the services that consume them.

## Assertions

Assert on returned values, state transitions and user-visible outcomes. In loops and data providers, pass a failure message that interpolates the active case (the DDQG category, in `FindPackagesByDdqgCategoryTest`) and its expectation.

## Test doubles

Doubles are handmade: in-memory fakes, or anonymous classes implementing the interface (`RangesProviderStub` serves fixed DDQG ranges or a fetch failure). Handmade doubles are refactor-safe and checked by PHPStan. Where Composer ships an in-memory implementation (for example `InstalledArrayRepository`, `LockArrayRepository`, `RootPackage`), use it.

Dynamic mocks (`$this->createMock()`) are reserved for wide Composer/Symfony interfaces with a deep surface (for example `Composer\IO\IOInterface`, `Composer\Composer`, `Composer\Config`, `Composer\Util\HttpDownloader`), when a handmade double would need excessive boilerplate. Domain and Application ports are always handmade.

## New test directories

List each new test directory in `phpunit.xml.dist`. Scanning all of `tests/` would fail: `tests/fixtures` holds vendored copies of test classes.
