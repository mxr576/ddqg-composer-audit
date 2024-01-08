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

namespace mxr576\ddqgComposerAudit\Domain\SecurityAdvisory;

use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\DeprecatedPackageVersionsProvider;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\Exception\PackageVersionsCouldNotBeFetched;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\InsecurePackageVersionsProvider;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\NonDrupal10CompatiblePackageVersionsProvider;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\UnsupportedPackageVersionsProvider;

/**
 * @internal
 */
final class SecurityAdvisoryFinderFromProblematicPackageProvider implements SecurityAdvisoryFinder
{
    public function __construct(private readonly ProblematicPackageVersionsProvider $provider, private readonly VersionParser $versionParser)
    {
    }

    public function find(array $packageConstraintMap): array
    {
        $advisories = [];
        $filter_by_packages = array_keys($packageConstraintMap);
        try {
            foreach ($this->provider->findByPackages(...$filter_by_packages) as $package_name => $version_range) {
                $package_version_constraint = $this->versionParser->parseConstraints($version_range);
                if ($package_version_constraint->matches($packageConstraintMap[$package_name])) {
                    // getVersion() only exists on \Composer\Semver\Constraint\Constraint::getVersion().
                    // \Composer\Semver\Constraint\ConstraintInterface::getPrettyString()
                    // returns a version constraint.
                    // Let's try to figure out the installed version from something
                    // that is part of the interface and returns a version
                    // string (even if it uses Composer's internal 4 digit
                    // representation).
                    $installed_version = $this->versionParser->normalize($packageConstraintMap[$package_name]->getLowerBound()
                      ->getVersion());
                    $advisory = new SecurityAdvisoryBuilder($package_name, $installed_version, $package_version_constraint);
                    if ($this->provider instanceof UnsupportedPackageVersionsProvider) {
                        $advisory->becauseUnsupported();
                    } elseif ($this->provider instanceof DeprecatedPackageVersionsProvider) {
                        $advisory->becauseDeprecated();
                    } elseif ($this->provider instanceof InsecurePackageVersionsProvider) {
                        $advisory->becauseInsecure();
                    } elseif ($this->provider instanceof NonDrupal10CompatiblePackageVersionsProvider) {
                        $advisory->becauseNotCompatibleWithDrupal10();
                    }

                    $advisories[$package_name] = [
                      $advisory->build(),
                    ];
                }
            }
        } catch (PackageVersionsCouldNotBeFetched $e) {
            throw new UnexpectedSecurityAdvisoryFinderException($e->getMessage(), $e->getCode(), $e);
        }

        return $advisories;
    }
}
