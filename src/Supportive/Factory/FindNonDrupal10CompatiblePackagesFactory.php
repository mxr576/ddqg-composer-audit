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

use Composer\Package\RootPackageInterface;
use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Application\PackageFinder\FindNonDrupal10CompatiblePackages;
use mxr576\ddqgComposerAudit\Domain\PackageMetadata\PackageMetadataLookup;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\NonDrupal10CompatiblePackageFinderConfigurationProvider;
use mxr576\ddqgComposerAudit\Supportive\Infrastructure\Composer\NonDrupal10CompatiblePackageVersionsProviderFromPackageMetadata;

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

    /**
     * Whether the D10 check is on, from `extra` or the environment.
     *
     * The setting is fixed for the whole Composer run, so the wiring decides
     * once whether to register the finder at all.
     */
    public function isCheckEnabled(): bool
    {
        return (new NonDrupal10CompatiblePackageFinderConfigurationProvider($this->rootPackage))->isEnabled();
    }

    public function create(PackageMetadataLookup $packageMetadataLookup): FindNonDrupal10CompatiblePackages
    {
        return new FindNonDrupal10CompatiblePackages(
            new NonDrupal10CompatiblePackageVersionsProviderFromPackageMetadata($packageMetadataLookup, $this->versionParser),
            $this->versionParser,
        );
    }
}
