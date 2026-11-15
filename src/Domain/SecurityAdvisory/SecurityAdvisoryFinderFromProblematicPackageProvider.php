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

namespace mxr576\ddqgComposerAudit\Domain\SecurityAdvisory;

use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Domain\DdqgCategory;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\Exception\PackageVersionsCouldNotBeFetched;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider;

/**
 * @internal
 */
final class SecurityAdvisoryFinderFromProblematicPackageProvider implements SecurityAdvisoryFinder
{
    public function __construct(private readonly ProblematicPackageVersionsProvider $provider, private readonly VersionParser $versionParser, private readonly DdqgCategory $category)
    {
    }

    public function find(array $packageConstraintMap): array
    {
        $advisories = [];
        try {
            foreach ($this->provider->findByPackages($packageConstraintMap) as $package_name => $version_range) {
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
                    $advisory = new SecurityAdvisoryBuilder($this->category, $package_name, $installed_version, $package_version_constraint);

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
