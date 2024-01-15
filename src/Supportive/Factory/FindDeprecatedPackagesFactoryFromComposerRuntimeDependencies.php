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

use Composer\EventDispatcher\EventDispatcher;
use Composer\Package\RootPackageInterface;
use Composer\Semver\VersionParser;
use Composer\Util\HttpDownloader;
use mxr576\ddqgComposerAudit\Application\PackageFinder\FindDeprecatedPackages;
use mxr576\ddqgComposerAudit\Domain\InstalledPackages\InstalledPackagesReadOnlyRepository;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\DeprecatedPackageFinderConfigurationProvider;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\DeprecatedPackageIgnoreRulesFromConfiguration;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\DeprecatedPackageVersionsFromLatestDdqgBuild;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\DeprecatedPackageVersionsFromSnapshotForTesting;
use mxr576\ddqgComposerAudit\Supportive\Adapter\Composer\Psr14EventDispatcherAdapterForComposer;
use mxr576\ddqgComposerAudit\Supportive\Util\Environment;

/**
 * @internal
 */
final class FindDeprecatedPackagesFactoryFromComposerRuntimeDependencies
{
    public function __construct(
        private readonly RootPackageInterface $project,
        private readonly HttpDownloader $httpDownloader,
        private readonly EventDispatcher $eventDispatcher,
        private readonly VersionParser $versionParser,
        private readonly InstalledPackagesReadOnlyRepository $installedPackagesReadOnlyRepository
    ) {
    }

    public function create(): FindDeprecatedPackages
    {
        return new FindDeprecatedPackages(
            // @todo Find a better place for this logic.
            Environment::isTestEnvironment() ? new DeprecatedPackageVersionsFromSnapshotForTesting() : new DeprecatedPackageVersionsFromLatestDdqgBuild($this->httpDownloader),
            $this->versionParser,
            new Psr14EventDispatcherAdapterForComposer($this->eventDispatcher),
            new DeprecatedPackageIgnoreRulesFromConfiguration(new DeprecatedPackageFinderConfigurationProvider($this->project, $this->versionParser)),
            $this->installedPackagesReadOnlyRepository
        );
    }
}
