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

namespace mxr576\ddqgComposerAudit\Infrastructure\Composer;

use Composer\Package\PackageInterface;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Domain\PackageMetadata\PackageMetadataLookup;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider;

/**
 * @internal
 */
final readonly class NonDrupal10CompatiblePackageVersionsProviderFromPackageMetadata implements ProblematicPackageVersionsProvider
{
    private const array DRUPAL_PACKAGE_TYPES = [
        'drupal-module',
        'drupal-theme',
        'drupal-profile',
        'drupal-custom-module',
        'drupal-custom-theme',
        'drupal-custom-profile',
    ];

    private const array DRUPAL_CORE_PACKAGE_CANDIDATES_IN_ORDER = [
        'drupal/core',
        'drupal/core-recommended',
    ];

    private const array DRUPAL_CORE_DEV_PACKAGE_CANDIDATES_IN_ORDER = [
        'drupal/core-dev',
    ];

    public function __construct(private PackageMetadataLookup $packageMetadataLookup, private VersionParser $versionParser)
    {
    }

    public function findByPackages(array $packageConstraintMap): array
    {
        $result = [];

        foreach ($packageConstraintMap as $package_name => $constraint) {
            // Each candidate version (e.g, of an update) is judged on its own.
            $offending_versions = [];
            foreach ($this->candidateVersions($constraint) as $candidate) {
                $package = $this->packageMetadataLookup->find($package_name, $candidate);
                if ($package instanceof PackageInterface && $this->isNotDrupal10Compatible($package)) {
                    $offending_versions[] = $package->getPrettyVersion();
                }
            }

            if ([] !== $offending_versions) {
                $result[$package_name] = implode(' || ', array_unique($offending_versions));
            }
        }

        return $result;
    }

    /**
     * @return ConstraintInterface[]
     */
    private function candidateVersions(ConstraintInterface $constraint): array
    {
        if ($constraint instanceof MultiConstraint && $constraint->isDisjunctive()) {
            return array_merge(...array_map($this->candidateVersions(...), $constraint->getConstraints()));
        }

        return [$constraint];
    }

    private function isNotDrupal10Compatible(PackageInterface $package): bool
    {
        if (!in_array($package->getType(), self::DRUPAL_PACKAGE_TYPES, true)) {
            return false;
        }

        $drupal_core_dep = null;
        foreach (self::DRUPAL_CORE_PACKAGE_CANDIDATES_IN_ORDER as $drupal_core_pkg_name) {
            if (array_key_exists($drupal_core_pkg_name, $package->getRequires())) {
                $drupal_core_dep = $package->getRequires()[$drupal_core_pkg_name];
                break;
            }
        }
        if (null === $drupal_core_dep) {
            foreach (self::DRUPAL_CORE_DEV_PACKAGE_CANDIDATES_IN_ORDER as $drupal_core_pkg_name) {
                if (array_key_exists($drupal_core_pkg_name, $package->getDevRequires())) {
                    $drupal_core_dep = $package->getDevRequires()[$drupal_core_pkg_name];
                    break;
                }
            }
        }

        if (null === $drupal_core_dep) {
            // @todo Consider logging this event.
            //   For that, we may need a PSR3 compliant logger wrapper
            //   around Composer IO.
            return false;
        }

        return !(new Constraint('>=', $this->versionParser->normalize('10.0.0')))->matches($drupal_core_dep->getConstraint());
    }
}
