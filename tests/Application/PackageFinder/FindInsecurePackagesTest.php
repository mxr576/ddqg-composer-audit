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

use mxr576\ddqgComposerAudit\Application\PackageFinder\Exception\UnexpectedPackageFinderException;
use mxr576\ddqgComposerAudit\Application\PackageFinder\FindInsecurePackages;
use mxr576\ddqgComposerAudit\Application\PackageFinder\PackageFinder;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\DdqgBranch;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\PackageVersionsFromSnapshotForTesting;

/**
 * Owns the version range matching rules, which all finders share.
 */
final class FindInsecurePackagesTest extends PackageFinderTestCase
{
    /**
     * @throws UnexpectedPackageFinderException
     */
    public function testExactVersionOutsideRangeYieldsNothing(): void
    {
        $finder = $this->createFinderWithRanges(['drupal/foo' => '>=1.0.0,<2.0.0']);

        self::assertSame([], $finder(['drupal/foo' => $this->exact('2.5.0')]));
    }

    /**
     * @throws UnexpectedPackageFinderException
     */
    public function testOrOfCandidatesMatchesOnlyOffendingCandidates(): void
    {
        $finder = $this->createFinderWithRanges(['drupal/foo' => '>=1.0.0,<2.0.0']);

        $result = $finder(['drupal/foo' => $this->versionParser()->parseConstraints('=1.5.0 || =2.5.0')]);

        self::assertCount(1, $result['drupal/foo']);
        $affected = $result['drupal/foo'][0]->affectedVersions;
        self::assertTrue($affected->matches($this->exact('1.5.0')), 'Offending candidate 1.5.0 is affected.');
        self::assertFalse($affected->matches($this->exact('2.5.0')), 'Non-offending candidate 2.5.0 is not affected.');
    }

    protected function snapshotPackageName(): string
    {
        return 'drupal/addtoany';
    }

    protected function snapshotOffendingVersion(): string
    {
        return '1.5.0';
    }

    protected function expectedSnapshotAdvisoryId(): string
    {
        // Insecure advisory IDs do not contain the version.
        return 'DDQG-insecure-drupal-addtoany';
    }

    protected function createSnapshotFinder(): PackageFinder
    {
        return new FindInsecurePackages(new PackageVersionsFromSnapshotForTesting(DdqgBranch::Insecure), $this->versionParser());
    }

    protected function createFinderWithRanges(array $ranges, bool $fetchFails = false): PackageFinder
    {
        return new FindInsecurePackages(new class($ranges, $fetchFails) implements ProblematicPackageVersionsProvider {
            use RangesProviderStub;
        }, $this->versionParser());
    }
}
