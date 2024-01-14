<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023-2024 Dezső Biczó
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/mxr576/ddqg-composer-audit/LICENSE.md
 *
 */

namespace mxr576\ddqgComposerAudit\Supportive\Factory;

use Composer\Package\Locker;
use Composer\Package\RootPackageInterface;
use Composer\Repository\InstalledRepository;
use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Application\PackageFinder\FindNonDrupal10CompatiblePackages;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\InstalledPackagesReadOnlyRepository;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\NonDrupal10CompatiblePackageFinderConfigurationProvider;
use mxr576\ddqgComposerAudit\Supportive\Infrastructure\Composer\NonDrupal10CompatiblePackageVersionsProviderFromComposerLock;

/**
 * @internal
 */
final class FindNonDrupal10CompatiblePackagesFactory
{
    public function __construct(
        private readonly RootPackageInterface $rootPackage,
        private readonly VersionParser $versionParser,
    ) {
    }

    public function inInstalledDependencies(InstalledRepository $installedRepository): FindNonDrupal10CompatiblePackages
    {
        return new FindNonDrupal10CompatiblePackages(
            new NonDrupal10CompatiblePackageVersionsProviderFromComposerLock(InstalledPackagesReadOnlyRepository::fromInstalledPackages($installedRepository), $this->versionParser),
            $this->versionParser,
            new NonDrupal10CompatiblePackageFinderConfigurationProvider($this->rootPackage)
        );
    }

    public function inInstalledRequiredDependencies(InstalledRepository $installedRepository): FindNonDrupal10CompatiblePackages
    {
        return new FindNonDrupal10CompatiblePackages(
            new NonDrupal10CompatiblePackageVersionsProviderFromComposerLock(InstalledPackagesReadOnlyRepository::fromInstalledRequiredPackages($installedRepository, $this->rootPackage), $this->versionParser),
            $this->versionParser,
            new NonDrupal10CompatiblePackageFinderConfigurationProvider($this->rootPackage)
        );
    }

    public function inLockedDependencies(Locker $locker): FindNonDrupal10CompatiblePackages
    {
        return new FindNonDrupal10CompatiblePackages(
            new NonDrupal10CompatiblePackageVersionsProviderFromComposerLock(InstalledPackagesReadOnlyRepository::fromLocker($locker, true), $this->versionParser),
            $this->versionParser,
            new NonDrupal10CompatiblePackageFinderConfigurationProvider($this->rootPackage)
        );
    }

    public function inLockedRequiredDependencies(Locker $locker): FindNonDrupal10CompatiblePackages
    {
        return new FindNonDrupal10CompatiblePackages(
            new NonDrupal10CompatiblePackageVersionsProviderFromComposerLock(InstalledPackagesReadOnlyRepository::fromLocker($locker, false), $this->versionParser),
            $this->versionParser,
            new NonDrupal10CompatiblePackageFinderConfigurationProvider($this->rootPackage)
        );
    }
}
