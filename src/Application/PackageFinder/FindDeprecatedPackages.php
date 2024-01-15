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

namespace mxr576\ddqgComposerAudit\Application\PackageFinder;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Event\DeprecatedPackageWasIgnored;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Exception\UnexpectedPackageFinderException;
use mxr576\ddqgComposerAudit\Domain\InstalledPackages\InstalledPackagesReadOnlyRepository;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\CompositePackageIgnoreRuleProvider;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\IgnorePackageByInstalledVersionOfOtherPackage;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRuleOptimizer;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRuleProvider;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\DeprecatedPackageVersionsProvider;
use mxr576\ddqgComposerAudit\Domain\SecurityAdvisory\SecurityAdvisoryFinder;
use mxr576\ddqgComposerAudit\Domain\SecurityAdvisory\SecurityAdvisoryFinderFromProblematicPackageProvider;
use mxr576\ddqgComposerAudit\Domain\SecurityAdvisory\UnexpectedSecurityAdvisoryFinderException;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Implements a use-case related to deprecated package discovery.
 *
 * @internal
 */
final class FindDeprecatedPackages implements PackageFinder
{
    use PackageIgnoreRuleOptimizer;

    /**
     * @var array<string,array<\mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRule>>
     */
    private ?array $optimizedIgnoreRules = null;

    private readonly SecurityAdvisoryFinder $securityAdvisoryFinder;

    private readonly PackageIgnoreRuleProvider $packageIgnoreRuleProvider;

    public function __construct(
        DeprecatedPackageVersionsProvider $unsupportedPackageVersionsProvider,
        VersionParser $versionParser,
        private readonly EventDispatcherInterface $eventDispatcher,
        PackageIgnoreRuleProvider $packageIgnoreRuleProvider,
        InstalledPackagesReadOnlyRepository $installedPackagesReadOnlyRepository
    ) {
        // This is a sufficient implementation for the one and only known
        // edge case.
        $this->packageIgnoreRuleProvider = new CompositePackageIgnoreRuleProvider($packageIgnoreRuleProvider, new class($installedPackagesReadOnlyRepository) implements PackageIgnoreRuleProvider {
            public function __construct(private readonly InstalledPackagesReadOnlyRepository $installedPackagesReadOnlyRepository)
            {
            }

            public function getIgnoreRules(): iterable
            {
                // @see https://github.com/mxr576/ddqg-composer-audit/issues/18
                yield new IgnorePackageByInstalledVersionOfOtherPackage(
                    $this->installedPackagesReadOnlyRepository,
                    'drupal/variationcache',
                    'drupal/core',
                    new Constraint(Constraint::STR_OP_GE, '10.2.0'),
                );
            }
        });
        $this->securityAdvisoryFinder = new SecurityAdvisoryFinderFromProblematicPackageProvider($unsupportedPackageVersionsProvider, $versionParser);
    }

    public function __invoke(array $packageConstraintMap): array
    {
        try {
            $result = $this->securityAdvisoryFinder->find($packageConstraintMap);
        } catch (UnexpectedSecurityAdvisoryFinderException $e) {
            throw new UnexpectedPackageFinderException($e->getMessage(), $e->getCode(), $e);
        }

        if ([] !== $result) {
            if (null === $this->optimizedIgnoreRules) {
                $this->optimizedIgnoreRules = $this->optimizePackageIgnoreRules($this->packageIgnoreRuleProvider);
            }

            foreach (array_keys($result) as $package_name) {
                if (array_key_exists($package_name, $this->optimizedIgnoreRules)) {
                    foreach ($this->optimizedIgnoreRules[$package_name] as $rule) {
                        if ($rule->evaluate($package_name, $packageConstraintMap[$package_name])) {
                            $this->eventDispatcher->dispatch(
                                new DeprecatedPackageWasIgnored(
                                    $package_name,
                                    $rule,
                                    $result[$package_name])
                            );
                            unset($result[$package_name]);
                        }
                    }
                }
            }
        }

        return $result;
    }
}
