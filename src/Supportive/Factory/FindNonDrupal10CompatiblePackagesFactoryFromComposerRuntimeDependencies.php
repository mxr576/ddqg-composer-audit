<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Dezső Biczó
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/mxr576/ddqg-composer-audit/LICENSE.md
 *
 */

namespace mxr576\ddqgComposerAudit\Supportive\Factory;

use Composer\Package\RootPackageInterface;
use Composer\Repository\LockArrayRepository;
use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Application\PackageFinder\FindNonDrupal10CompatiblePackages;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\NonDrupal10CompatiblePackageFinderConfigurationProvider;
use mxr576\ddqgComposerAudit\Supportive\Infrastructure\Composer\NonDrupal10CompatiblePackageVersionsProviderFromComposerLock;

/**
 * @internal
 */
final class FindNonDrupal10CompatiblePackagesFactoryFromComposerRuntimeDependencies
{
    public function __construct(
        private readonly RootPackageInterface $rootPackage,
        private readonly LockArrayRepository $lockRepository,
        private readonly VersionParser $versionParser,
    ) {
    }

    public function create(): FindNonDrupal10CompatiblePackages
    {
        return new FindNonDrupal10CompatiblePackages(
            new NonDrupal10CompatiblePackageVersionsProviderFromComposerLock($this->lockRepository, $this->versionParser),
            $this->versionParser,
            new NonDrupal10CompatiblePackageFinderConfigurationProvider($this->rootPackage)
        );
    }
}
