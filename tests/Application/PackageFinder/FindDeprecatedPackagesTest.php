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

use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\InstalledRepository;
use mxr576\ddqgComposerAudit\Application\PackageFinder\FindDeprecatedPackages;
use mxr576\ddqgComposerAudit\Application\PackageFinder\PackageFinder;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRuleProvider;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\DeprecatedPackageVersionsProvider;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\InstalledPackagesReadOnlyRepository;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\DeprecatedPackageVersionsFromSnapshotForTesting;

final class FindDeprecatedPackagesTest extends PackageFinderTestCase
{
    protected function snapshotPackageName(): string
    {
        return 'drupal/ablock';
    }

    protected function snapshotOffendingVersion(): string
    {
        // The snapshot marks every version of the package as deprecated.
        return '1.0.0';
    }

    protected function expectedSnapshotAdvisoryId(): string
    {
        return 'DDQG-deprecated-drupal-ablock-1.0.0.0';
    }

    protected function createSnapshotFinder(): PackageFinder
    {
        return $this->build(new DeprecatedPackageVersionsFromSnapshotForTesting());
    }

    protected function createFinderWithRanges(array $ranges, bool $fetchFails = false): PackageFinder
    {
        return $this->build(new class($ranges, $fetchFails) implements DeprecatedPackageVersionsProvider {
            use RangesProviderStub;
        });
    }

    private function build(DeprecatedPackageVersionsProvider $provider): FindDeprecatedPackages
    {
        return new FindDeprecatedPackages(
            $provider,
            $this->versionParser(),
            new NullEventDispatcher(),
            new class implements PackageIgnoreRuleProvider {
                public function getIgnoreRules(): iterable
                {
                    return [];
                }
            },
            InstalledPackagesReadOnlyRepository::fromInstalledPackages(new InstalledRepository([new InstalledArrayRepository()])),
        );
    }
}
