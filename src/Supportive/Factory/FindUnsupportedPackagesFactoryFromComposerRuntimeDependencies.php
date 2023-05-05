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

use Composer\EventDispatcher\EventDispatcher;
use Composer\Package\RootPackageInterface;
use Composer\Semver\VersionParser;
use Composer\Util\HttpDownloader;
use mxr576\ddqgComposerAudit\Application\PackageFinder\FindUnsupportedPackages;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\UnsupportedPackageFinderConfigurationUsingCweagansConfigurablePlugin;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\UnsupportedPackageVersionsFromLatestDdqgBuild;
use mxr576\ddqgComposerAudit\Supportive\Adapter\Composer\Psr14EventDispatcherAdapterForComposer;

/**
 * @internal
 */
final class FindUnsupportedPackagesFactoryFromComposerRuntimeDependencies
{
    public function __construct(
        private readonly RootPackageInterface $project,
        private readonly HttpDownloader $httpDownloader,
        private readonly EventDispatcher $eventDispatcher,
        private readonly VersionParser $versionParser,
    ) {
    }

    public function create(): FindUnsupportedPackages
    {
        return new FindUnsupportedPackages(
            new UnsupportedPackageVersionsFromLatestDdqgBuild($this->httpDownloader),
            $this->versionParser,
            new Psr14EventDispatcherAdapterForComposer($this->eventDispatcher),
            new UnsupportedPackageFinderConfigurationUsingCweagansConfigurablePlugin($this->project, $this->versionParser)
        );
    }
}
