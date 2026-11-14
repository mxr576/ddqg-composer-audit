# Writing tests

Apply every rule below to each test you add or change.

## Seams

The suite has two seams. Test each rule once, at the lowest seam that owns it:

- **Package finder** (`PackageFinder`), covered by PHPUnit. Call a finder with the map Composer would pass (package name to an exact version, or an OR of exact versions) and assert on the advisories it returns. Domain classes such as `SecurityAdvisoryFinderFromProblematicPackageProvider` and the package metadata lookup are covered through the finders that call them.
- **Composer to plugin**, covered by the e2e test (see `AGENTS.md`). It is the acceptance test for Composer integration.

A rule shared by all finders (for example range matching) gets one test, through one finder. Per-category behaviour (advisory ID, exception wrapping, ignore rules) gets a test per finder. Cases every finder shares live in `PackageFinderTestCase`: each finder's test class extends it and supplies its snapshot package, offending version, advisory ID and finder builders.

## What earns a test

Every test must be able to fail for a plausible bug in observable behaviour through a public interface, and survive internal refactors. Domain behaviour, boundary invariants, transformations, parsers/serializers, data integrity and real bug regressions earn tests. Pure DTOs, `readonly` value objects, domain events, constants/config and pass-through glue are covered through the services that consume them.

## Assertions

Assert on returned values, state transitions and user-visible outcomes. In loops and data providers, pass a failure message that interpolates the active case and its expectation.

## Test doubles

Doubles are handmade: in-memory fakes, or anonymous classes implementing the interface (`RangesProviderStub` serves fixed DDQG ranges or a fetch failure). Handmade doubles are refactor-safe and checked by PHPStan. Where Composer ships an in-memory implementation (for example `InstalledArrayRepository`, `LockArrayRepository`, `RootPackage`), use it.

Dynamic mocks (`$this->createMock()`) are reserved for wide Composer/Symfony interfaces with a deep surface (for example `Composer\IO\IOInterface`, `Composer\Composer`, `Composer\Config`, `Composer\Util\HttpDownloader`), when a handmade double would need excessive boilerplate. Domain and Application ports are always handmade.

## New test directories

List each new test directory in `phpunit.xml.dist`. Scanning all of `tests/` would fail: `tests/fixtures` holds vendored copies of test classes.
