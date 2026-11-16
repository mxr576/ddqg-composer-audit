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

use Composer\Semver\Comparator;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\MultiConstraint;
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
                    $offending_version = $this->offendingVersion($packageConstraintMap[$package_name], $package_version_constraint);
                    $advisory = new SecurityAdvisoryBuilder($this->category, $package_name, $offending_version, $package_version_constraint);

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

    /**
     * Names the offending candidate: the lowest candidate version that falls
     * inside the problematic range.
     *
     * Composer may pass an OR of exact versions (e.g., update candidates), so
     * the advisory must name a candidate that is actually affected.
     *
     * @param ConstraintInterface $auditedCandidates
     *   One package's entry of the audited set, already known to match the
     *   problematic range.
     */
    private function offendingVersion(ConstraintInterface $auditedCandidates, ConstraintInterface $problematic): string
    {
        $lowest = null;
        foreach ($this->candidates($auditedCandidates) as $candidate) {
            if (!$problematic->matches($candidate)) {
                continue;
            }
            $version = $this->versionOf($candidate);
            if (null === $lowest || Comparator::lessThan($version, $lowest)) {
                $lowest = $version;
            }
        }

        // A disjunctive constraint matches only through one of its parts, so a
        // matching entry always has an offending candidate.
        return $lowest ?? throw new \LogicException('The audited candidates match the problematic range, but none of them does on its own.');
    }

    /**
     * @return ConstraintInterface[]
     */
    private function candidates(ConstraintInterface $constraint): array
    {
        if ($constraint instanceof MultiConstraint && $constraint->isDisjunctive()) {
            return array_merge(...array_map($this->candidates(...), $constraint->getConstraints()));
        }

        return [$constraint];
    }

    private function versionOf(ConstraintInterface $constraint): string
    {
        // getVersion() only exists on \Composer\Semver\Constraint\Constraint::getVersion().
        // \Composer\Semver\Constraint\ConstraintInterface::getPrettyString()
        // returns a version constraint.
        // Let's try to figure out the installed version from something
        // that is part of the interface and returns a version
        // string (even if it uses Composer's internal 4 digit
        // representation).
        return $this->versionParser->normalize($constraint->getLowerBound()->getVersion());
    }
}
