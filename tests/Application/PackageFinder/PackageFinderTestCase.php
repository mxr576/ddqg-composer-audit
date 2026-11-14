<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023-2026 Dezső Biczó
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/mxr576/ddqg-composer-audit/LICENSE.md
 *
 */

namespace mxr576\ddqgComposerAudit\Tests\Application\PackageFinder;

use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Exception\UnexpectedPackageFinderException;
use mxr576\ddqgComposerAudit\Application\PackageFinder\PackageFinder;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that go through the package finder interface.
 */
abstract class PackageFinderTestCase extends TestCase
{
    /**
     * @throws UnexpectedPackageFinderException
     */
    final public function testExactVersionInsideRangeYieldsAdvisory(): void
    {
        $package = $this->snapshotPackageName();

        $result = $this->createSnapshotFinder()([$package => $this->exact($this->snapshotOffendingVersion())]);

        self::assertSame([$package], array_keys($result));
        self::assertCount(1, $result[$package]);
        self::assertSame($package, $result[$package][0]->packageName);
        self::assertSame($this->expectedSnapshotAdvisoryId(), $result[$package][0]->advisoryId);
    }

    /**
     * @throws UnexpectedPackageFinderException
     */
    final public function testFetchFailureSurfacesAsFinderException(): void
    {
        $this->expectException(UnexpectedPackageFinderException::class);

        $this->createFinderWithRanges([], true)(['drupal/foo' => $this->exact('1.0.0')]);
    }

    /**
     * A package that the DDQG snapshot of the category lists.
     */
    abstract protected function snapshotPackageName(): string;

    /**
     * A version of the snapshot package that DDQG flags.
     */
    abstract protected function snapshotOffendingVersion(): string;

    /**
     * The advisory ID expected for the offending version of the snapshot package.
     */
    abstract protected function expectedSnapshotAdvisoryId(): string;

    /**
     * Builds a package finder from the DDQG snapshot of its category.
     */
    abstract protected function createSnapshotFinder(): PackageFinder;

    /**
     * Builds a package finder whose DDQG source serves the given ranges.
     *
     * @param array<string,string> $ranges
     */
    abstract protected function createFinderWithRanges(array $ranges, bool $fetchFails = false): PackageFinder;

    final protected function versionParser(): VersionParser
    {
        return new VersionParser();
    }

    final protected function exact(string $version): ConstraintInterface
    {
        return $this->versionParser()->parseConstraints('=' . $version);
    }
}
