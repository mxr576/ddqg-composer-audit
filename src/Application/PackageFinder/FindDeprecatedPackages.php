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

use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Event\DeprecatedPackageWasIgnored;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Exception\UnexpectedPackageFinderException;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Type\PackageIgnoreRule;
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
    /**
     * @var array<string,array<\Composer\Semver\Constraint\ConstraintInterface>>
     */
    private ?array $optimizedIgnoreRules = null;

    private readonly SecurityAdvisoryFinder $securityAdvisoryFinder;

    public function __construct(
        DeprecatedPackageVersionsProvider $unsupportedPackageVersionsProvider,
        VersionParser $versionParser,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DeprecatedPackageFinderConfigurationProvider $configurationProvider
    ) {
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
                /** @var \ArrayObject<string,array<\Composer\Semver\Constraint\ConstraintInterface>> $tmp */
                $tmp = array_reduce($this->configurationProvider->getDeprecatedPackageIgnoreRules(),
                    static function (\ArrayObject $carry, PackageIgnoreRule $item) {
                        $carry[$item->packageName][] = $item->rule;

                        return $carry;
                    }, new \ArrayObject());
                $this->optimizedIgnoreRules = $tmp->getArrayCopy();
            }
            foreach (array_keys($result) as $package_name) {
                if (array_key_exists($package_name, $this->optimizedIgnoreRules)) {
                    foreach ($this->optimizedIgnoreRules[$package_name] as $constraint) {
                        if ($constraint->matches($packageConstraintMap[$package_name])) {
                            $this->eventDispatcher->dispatch(
                                new DeprecatedPackageWasIgnored(
                                    $package_name,
                                    new PackageIgnoreRule($package_name, $constraint),
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
