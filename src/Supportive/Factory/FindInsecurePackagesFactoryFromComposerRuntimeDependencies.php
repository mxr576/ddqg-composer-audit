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

namespace mxr576\ddqgComposerAudit\Supportive\Factory;

use Composer\Semver\VersionParser;
use Composer\Util\HttpDownloader;
use mxr576\ddqgComposerAudit\Application\PackageFinder\FindInsecurePackages;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\DdqgBranch;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\PackageVersionsFromLatestDdqgBuild;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\PackageVersionsFromSnapshotForTesting;
use mxr576\ddqgComposerAudit\Supportive\Util\Environment;

/**
 * @internal
 */
final class FindInsecurePackagesFactoryFromComposerRuntimeDependencies
{
    public function __construct(
        private readonly HttpDownloader $httpDownloader,
        private readonly VersionParser $versionParser,
    ) {
    }

    public function create(): FindInsecurePackages
    {
        return new FindInsecurePackages(
            // @todo Find a better place for this logic.
            Environment::isTestEnvironment() ? new PackageVersionsFromSnapshotForTesting(DdqgBranch::Insecure) : new PackageVersionsFromLatestDdqgBuild(DdqgBranch::Insecure, $this->httpDownloader), $this->versionParser);
    }
}
