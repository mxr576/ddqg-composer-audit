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

namespace mxr576\ddqgComposerAudit\Presentation\Composer\Wiring;

use Composer\EventDispatcher\EventDispatcher;
use Composer\Package\RootPackageInterface;
use Composer\Semver\VersionParser;
use Composer\Util\HttpDownloader;
use mxr576\ddqgComposerAudit\Application\PackageFinder\FindPackagesByDdqgCategory;
use mxr576\ddqgComposerAudit\Application\PackageFinder\PackageFinder;
use mxr576\ddqgComposerAudit\Domain\DdqgCategory;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\CompositePackageIgnoreRuleProvider;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\IgnoreVariationcacheMovedIntoCore;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\NoPackageIgnoreRules;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRuleProvider;
use mxr576\ddqgComposerAudit\Domain\PackageMetadata\PackageMetadataLookup;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\D10CompatibilityCheckSetting;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\DeprecatedPackageIgnoreRulesFromConfiguration;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\NonDrupal10CompatiblePackageVersionsProviderFromPackageMetadata;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\UnsupportedPackageIgnoreRulesFromConfiguration;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\DdqgBranch;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\PackageVersionsFromLatestDdqgBuild;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\PackageVersionsFromSnapshotForTesting;
use mxr576\ddqgComposerAudit\Presentation\Composer\Environment;
use mxr576\ddqgComposerAudit\Presentation\Composer\Event\Psr14EventDispatcherAdapterForComposer;

/**
 * Builds the package finder of a DDQG category.
 *
 * @internal
 */
final class PackageFinderFactory
{
    public function __construct(
        private readonly RootPackageInterface $project,
        private readonly HttpDownloader $httpDownloader,
        private readonly EventDispatcher $eventDispatcher,
        private readonly VersionParser $versionParser,
        private readonly PackageMetadataLookup $packageMetadataLookup,
    ) {
    }

    /**
     * Builds the finders of the enabled categories, each with its source.
     *
     * Insecure, unsupported and deprecated are always enabled, D10-incompatible
     * only when the D10 check is on (from `extra` or the environment). The
     * setting is fixed for the whole Composer run, so it is decided once here.
     *
     * @return PackageFinder[]
     *   In the order the repositories are to be prepended. Keep it: it
     *   decides the order of advisories in the audit output.
     */
    public function createForEnabledCategories(): array
    {
        $finders = [
            $this->create(DdqgCategory::Unsupported, $this->ddqgSource(DdqgBranch::Unsupported)),
            $this->create(DdqgCategory::Deprecated, $this->ddqgSource(DdqgBranch::Deprecated)),
            $this->create(DdqgCategory::Insecure, $this->ddqgSource(DdqgBranch::Insecure)),
        ];
        if ((new D10CompatibilityCheckSetting($this->project))->isEnabled()) {
            $finders[] = $this->create(DdqgCategory::D10Incompatible, new NonDrupal10CompatiblePackageVersionsProviderFromPackageMetadata($this->packageMetadataLookup, $this->versionParser));
        }

        return $finders;
    }

    private function create(DdqgCategory $category, ProblematicPackageVersionsProvider $source): PackageFinder
    {
        return new FindPackagesByDdqgCategory(
            $category,
            $source,
            $this->versionParser,
            new Psr14EventDispatcherAdapterForComposer($this->eventDispatcher),
            $this->ignoreRules($category),
        );
    }

    /**
     * The live DDQG branch, or its snapshot in the test environment.
     */
    private function ddqgSource(DdqgBranch $branch): ProblematicPackageVersionsProvider
    {
        return Environment::isTestEnvironment() ? new PackageVersionsFromSnapshotForTesting($branch) : new PackageVersionsFromLatestDdqgBuild($branch, $this->httpDownloader);
    }

    private function ignoreRules(DdqgCategory $category): PackageIgnoreRuleProvider
    {
        return match ($category) {
            DdqgCategory::Unsupported => new UnsupportedPackageIgnoreRulesFromConfiguration($this->project, $this->versionParser),
            DdqgCategory::Deprecated => new CompositePackageIgnoreRuleProvider(
                new DeprecatedPackageIgnoreRulesFromConfiguration($this->project, $this->versionParser),
                new IgnoreVariationcacheMovedIntoCore($this->packageMetadataLookup),
            ),
            default => new NoPackageIgnoreRules(),
        };
    }
}
