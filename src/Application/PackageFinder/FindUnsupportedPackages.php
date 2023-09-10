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

namespace mxr576\ddqgComposerAudit\Application\PackageFinder;

use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Event\UnsupportedPackageWasIgnored;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Exception\UnexpectedPackageFinderException;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Type\UnsupportedPackageIgnoreRule;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\UnsupportedPackageVersionsProvider;
use mxr576\ddqgComposerAudit\Domain\SecurityAdvisory\SecurityAdvisoryFinder;
use mxr576\ddqgComposerAudit\Domain\SecurityAdvisory\SecurityAdvisoryFinderFromProblematicPackageProvider;
use mxr576\ddqgComposerAudit\Domain\SecurityAdvisory\UnexpectedSecurityAdvisoryFinderException;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Implements a use-case related to unsupported package discovery.
 *
 * @internal
 */
final class FindUnsupportedPackages implements PackageFinder
{
    /**
     * @var array<string,array<\Composer\Semver\Constraint\ConstraintInterface>>
     */
    private ?array $optimizedIgnoreRules = null;

    private readonly SecurityAdvisoryFinder $securityAdvisoryFinder;

    public function __construct(
        UnsupportedPackageVersionsProvider $unsupportedPackageVersionsProvider,
        VersionParser $versionParser,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UnsupportedPackageFinderConfigurationProvider $configurationProvider
    ) {
        $this->securityAdvisoryFinder = new SecurityAdvisoryFinderFromProblematicPackageProvider($unsupportedPackageVersionsProvider,
            $versionParser);
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
                $this->optimizedIgnoreRules = array_reduce($this->configurationProvider->getUnsupportedPackageIgnoreRules(),
                    static function (array $carry, UnsupportedPackageIgnoreRule $item) {
                        $carry[$item->packageName][] = $item->rule;

                        return $carry;
                    }, []);
            }
            foreach (array_keys($result) as $package_name) {
                if (array_key_exists($package_name, $this->optimizedIgnoreRules)) {
                    foreach ($this->optimizedIgnoreRules[$package_name] as $constraint) {
                        if ($constraint->matches($packageConstraintMap[$package_name])) {
                            $this->eventDispatcher->dispatch(
                                new UnsupportedPackageWasIgnored($package_name,
                                    new UnsupportedPackageIgnoreRule($package_name, $constraint),
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
