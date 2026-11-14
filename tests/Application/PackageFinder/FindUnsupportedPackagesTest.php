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

use Composer\Package\RootPackage;
use mxr576\ddqgComposerAudit\Application\PackageFinder\FindUnsupportedPackages;
use mxr576\ddqgComposerAudit\Application\PackageFinder\PackageFinder;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\UnsupportedPackageVersionsProvider;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\UnsupportedPackageFinderConfigurationProvider;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\UnsupportedPackageIgnoreRulesFromConfiguration;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\UnsupportedPackageVersionsFromSnapshotForTesting;

final class FindUnsupportedPackagesTest extends PackageFinderTestCase
{
    protected function snapshotPackageName(): string
    {
        return 'drupal/a11y_form_helpers';
    }

    protected function snapshotOffendingVersion(): string
    {
        return '1.0.0';
    }

    protected function expectedSnapshotAdvisoryId(): string
    {
        return 'DDQG-unsupported-drupal-a11y_form_helpers-1.0.0.0';
    }

    protected function createSnapshotFinder(): PackageFinder
    {
        return $this->build(new UnsupportedPackageVersionsFromSnapshotForTesting());
    }

    protected function createFinderWithRanges(array $ranges, bool $fetchFails = false): PackageFinder
    {
        return $this->build(new class($ranges, $fetchFails) implements UnsupportedPackageVersionsProvider {
            use RangesProviderStub;
        });
    }

    private function build(UnsupportedPackageVersionsProvider $provider): FindUnsupportedPackages
    {
        return new FindUnsupportedPackages(
            $provider,
            $this->versionParser(),
            new NullEventDispatcher(),
            new UnsupportedPackageIgnoreRulesFromConfiguration(new UnsupportedPackageFinderConfigurationProvider(new RootPackage('test/root', '1.0.0.0', '1.0.0'), $this->versionParser())),
        );
    }
}
