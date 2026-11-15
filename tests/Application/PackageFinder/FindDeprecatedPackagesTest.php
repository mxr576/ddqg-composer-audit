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

use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\LockArrayRepository;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Exception\UnexpectedPackageFinderException;
use mxr576\ddqgComposerAudit\Application\PackageFinder\FindDeprecatedPackages;
use mxr576\ddqgComposerAudit\Application\PackageFinder\PackageFinder;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRuleProvider;
use mxr576\ddqgComposerAudit\Domain\PackageMetadata\PackageMetadataLookup;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\PackageMetadataLookupFromComposerRepositories;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\DdqgBranch;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\PackageVersionsFromSnapshotForTesting;

final class FindDeprecatedPackagesTest extends PackageFinderTestCase
{
    /**
     * VariationCache moved into core in 10.2.0, so it is only reported as
     * deprecated for projects that have such a core.
     *
     * @throws UnexpectedPackageFinderException
     */
    public function testVariationcacheIsFlaggedWhileCore102OrNewerIsInstalled(): void
    {
        $result = $this->variationcacheFinder(new InstalledArrayRepository([$this->package('drupal/core', '10.2.0')]), null)(['drupal/variationcache' => $this->exact('1.0.0')]);

        self::assertSame(['drupal/variationcache'], array_keys($result));
    }

    /**
     * @throws UnexpectedPackageFinderException
     */
    public function testVariationcacheIsFlaggedWhileCore102OrNewerIsLocked(): void
    {
        $result = $this->variationcacheFinder(new InstalledArrayRepository(), new LockArrayRepository([$this->package('drupal/core', '10.3.1')]))(['drupal/variationcache' => $this->exact('1.0.0')]);

        self::assertSame(['drupal/variationcache'], array_keys($result));
    }

    /**
     * @throws UnexpectedPackageFinderException
     */
    public function testVariationcacheIsNotFlaggedWithOlderOrNoCore(): void
    {
        $constraint = ['drupal/variationcache' => $this->exact('1.0.0')];

        $older = $this->variationcacheFinder(new InstalledArrayRepository([$this->package('drupal/core', '10.1.0')]), null)($constraint);
        $none = $this->variationcacheFinder(new InstalledArrayRepository(), null)($constraint);

        self::assertSame([], $older, 'Core 10.1.0 keeps the package ignored.');
        self::assertSame([], $none, 'Without core metadata and without a lock file the package stays ignored.');
    }

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
        return $this->build(new PackageVersionsFromSnapshotForTesting(DdqgBranch::Deprecated));
    }

    protected function createFinderWithRanges(array $ranges, bool $fetchFails = false): PackageFinder
    {
        return $this->build(new class($ranges, $fetchFails) implements ProblematicPackageVersionsProvider {
            use RangesProviderStub;
        });
    }

    private function package(string $name, string $version): PackageInterface
    {
        return new CompletePackage($name, $this->versionParser()->normalize($version), $version);
    }

    private function variationcacheFinder(InstalledArrayRepository $installed, ?LockArrayRepository $locked): FindDeprecatedPackages
    {
        return $this->build(new class(['drupal/variationcache' => '*']) implements ProblematicPackageVersionsProvider {
            use RangesProviderStub;
        }, new PackageMetadataLookupFromComposerRepositories($installed, static fn (): ?LockArrayRepository => $locked));
    }

    private function build(ProblematicPackageVersionsProvider $provider, ?PackageMetadataLookup $lookup = null): FindDeprecatedPackages
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
            $lookup ?? new PackageMetadataLookupFromComposerRepositories(new InstalledArrayRepository(), static fn (): ?LockArrayRepository => null),
        );
    }
}
