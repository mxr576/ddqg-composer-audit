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
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\LockArrayRepository;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Exception\UnexpectedPackageFinderException;
use mxr576\ddqgComposerAudit\Application\PackageFinder\FindNonDrupal10CompatiblePackages;
use mxr576\ddqgComposerAudit\Application\PackageFinder\NonDrupal10CompatiblePackageFinderConfigurationProvider;
use mxr576\ddqgComposerAudit\Application\PackageFinder\PackageFinder;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\NonDrupal10CompatiblePackageVersionsProvider;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\PackageMetadataLookupFromComposerRepositories;
use mxr576\ddqgComposerAudit\Supportive\Infrastructure\Composer\NonDrupal10CompatiblePackageVersionsProviderFromPackageMetadata;

/**
 * The D10 finder judges package metadata, not a DDQG snapshot: here the
 * "snapshot" is an installed module whose core requirement excludes Drupal 10.
 */
final class FindNonDrupal10CompatiblePackagesTest extends PackageFinderTestCase
{
    private const PACKAGE = 'drupal/foo';

    /**
     * @throws UnexpectedPackageFinderException
     */
    public function testVersionCompatibleWithDrupal10IsNotFlagged(): void
    {
        $installed = new InstalledArrayRepository([$this->module('1.0.0', '^9.4 || ^10')]);

        self::assertSame([], $this->finder($installed, null)([self::PACKAGE => $this->exact('1.0.0')]));
    }

    /**
     * @throws UnexpectedPackageFinderException
     */
    public function testVersionWithoutMetadataYieldsNothing(): void
    {
        $installed = new InstalledArrayRepository([$this->module('1.0.0', '^9.4')]);

        self::assertSame([], $this->finder($installed, null)([self::PACKAGE => $this->exact('2.0.0')]));
    }

    /**
     * @throws UnexpectedPackageFinderException
     */
    public function testOrOfCandidatesJudgesEachVersionOnItsOwn(): void
    {
        // The compatible version is installed, the incompatible one is locked.
        $installed = new InstalledArrayRepository([$this->module('1.0.0', '^10')]);
        $locked = new LockArrayRepository([$this->module('2.0.0', '^9.4')]);

        $result = $this->finder($installed, $locked)([self::PACKAGE => $this->versionParser()->parseConstraints('=1.0.0 || =2.0.0')]);

        self::assertCount(1, $result[self::PACKAGE] ?? [], 'The incompatible candidate 2.0.0 yields an advisory.');
        $affected = $result[self::PACKAGE][0]->affectedVersions;
        self::assertTrue($affected->matches($this->exact('2.0.0')), 'Incompatible candidate 2.0.0 is affected.');
        self::assertFalse($affected->matches($this->exact('1.0.0')), 'Compatible candidate 1.0.0 is not affected.');
    }

    /**
     * @throws UnexpectedPackageFinderException
     */
    public function testNothingIsFlaggedWhileCheckIsDisabled(): void
    {
        $installed = new InstalledArrayRepository([$this->module('1.0.0', '^9.4')]);

        self::assertSame([], $this->finder($installed, null, false)([self::PACKAGE => $this->exact('1.0.0')]));
    }

    /**
     * @throws UnexpectedPackageFinderException
     */
    public function testMetadataIsFoundInLockFileWhenNothingIsInstalled(): void
    {
        $locked = new LockArrayRepository([$this->module('1.0.0', '^9.4')]);

        $result = $this->finder(new InstalledArrayRepository(), $locked)([self::PACKAGE => $this->exact('1.0.0')]);

        self::assertSame([self::PACKAGE], array_keys($result));
    }

    protected function snapshotPackageName(): string
    {
        return self::PACKAGE;
    }

    protected function snapshotOffendingVersion(): string
    {
        return '1.0.0';
    }

    protected function expectedSnapshotAdvisoryId(): string
    {
        // D10 advisory IDs do not contain the version.
        return 'DDQG-D10-incompatible-drupal-foo';
    }

    protected function createSnapshotFinder(): PackageFinder
    {
        return $this->finder(new InstalledArrayRepository([$this->module('1.0.0', '^9.4')]), null);
    }

    protected function createFinderWithRanges(array $ranges, bool $fetchFails = false): PackageFinder
    {
        return $this->build(new class($ranges, $fetchFails) implements NonDrupal10CompatiblePackageVersionsProvider {
            use RangesProviderStub;
        }, true);
    }

    private function module(string $version, string $coreConstraint): PackageInterface
    {
        $package = new CompletePackage(self::PACKAGE, $this->versionParser()->normalize($version), $version);
        $package->setType('drupal-module');
        $package->setRequires(['drupal/core' => new Link(self::PACKAGE, 'drupal/core', $this->versionParser()->parseConstraints($coreConstraint), Link::TYPE_REQUIRE, $coreConstraint)]);

        return $package;
    }

    private function finder(InstalledArrayRepository $installed, ?LockArrayRepository $locked, bool $enabled = true): FindNonDrupal10CompatiblePackages
    {
        return $this->build(
            new NonDrupal10CompatiblePackageVersionsProviderFromPackageMetadata(
                new PackageMetadataLookupFromComposerRepositories($installed, static fn (): ?LockArrayRepository => $locked),
                $this->versionParser(),
            ),
            $enabled,
        );
    }

    private function build(NonDrupal10CompatiblePackageVersionsProvider $provider, bool $enabled): FindNonDrupal10CompatiblePackages
    {
        return new FindNonDrupal10CompatiblePackages(
            $provider,
            $this->versionParser(),
            new class($enabled) implements NonDrupal10CompatiblePackageFinderConfigurationProvider {
                public function __construct(private readonly bool $enabled)
                {
                }

                public function isEnabled(): bool
                {
                    return $this->enabled;
                }
            },
        );
    }
}
