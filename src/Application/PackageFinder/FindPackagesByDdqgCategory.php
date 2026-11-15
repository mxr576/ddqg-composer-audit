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

namespace mxr576\ddqgComposerAudit\Application\PackageFinder;

use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Event\AdvisoryWasIgnored;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Exception\UnexpectedPackageFinderException;
use mxr576\ddqgComposerAudit\Domain\DdqgCategory;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRule;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRuleOptimizer;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRuleProvider;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider;
use mxr576\ddqgComposerAudit\Domain\SecurityAdvisory\SecurityAdvisoryFinder;
use mxr576\ddqgComposerAudit\Domain\SecurityAdvisory\SecurityAdvisoryFinderFromProblematicPackageProvider;
use mxr576\ddqgComposerAudit\Domain\SecurityAdvisory\UnexpectedSecurityAdvisoryFinderException;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Finds the packages of the audited set that one DDQG category reports.
 *
 * @internal
 */
final class FindPackagesByDdqgCategory implements PackageFinder
{
    use PackageIgnoreRuleOptimizer;

    /**
     * @var array<string,array<PackageIgnoreRule>>|null
     */
    private ?array $optimizedIgnoreRules = null;

    private readonly SecurityAdvisoryFinder $securityAdvisoryFinder;

    public function __construct(
        private readonly DdqgCategory $category,
        ProblematicPackageVersionsProvider $source,
        VersionParser $versionParser,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly PackageIgnoreRuleProvider $packageIgnoreRuleProvider,
    ) {
        $this->securityAdvisoryFinder = new SecurityAdvisoryFinderFromProblematicPackageProvider($source, $versionParser, $category);
    }

    public function __invoke(array $packageConstraintMap): array
    {
        try {
            $result = $this->securityAdvisoryFinder->find($packageConstraintMap);
        } catch (UnexpectedSecurityAdvisoryFinderException $e) {
            throw new UnexpectedPackageFinderException($e->getMessage(), $e->getCode(), $e);
        }

        if ([] === $result) {
            return $result;
        }

        if (null === $this->optimizedIgnoreRules) {
            $this->optimizedIgnoreRules = $this->optimizePackageIgnoreRules($this->packageIgnoreRuleProvider);
        }

        foreach (array_keys($result) as $package_name) {
            foreach ($this->optimizedIgnoreRules[$package_name] ?? [] as $rule) {
                if ($rule->evaluate($package_name, $packageConstraintMap[$package_name])) {
                    $this->eventDispatcher->dispatch(
                        new AdvisoryWasIgnored($this->category, $package_name, $rule, $result[$package_name])
                    );
                    unset($result[$package_name]);
                }
            }
        }

        return $result;
    }
}
