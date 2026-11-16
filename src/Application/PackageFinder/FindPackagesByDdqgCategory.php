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

use Composer\Semver\Comparator;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Event\FindingWasIgnored;
use mxr576\ddqgComposerAudit\Domain\DdqgCategory;
use mxr576\ddqgComposerAudit\Domain\Finding\DdqgFinding;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRule;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRuleProvider;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\Exception\PackageVersionsCouldNotBeFetched;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Finds the packages of the audited set that one DDQG category reports.
 *
 * @internal
 */
final class FindPackagesByDdqgCategory
{
    /**
     * @var array<string,array<PackageIgnoreRule>>|null
     */
    private ?array $ignoreRulesByPackage = null;

    public function __construct(
        private readonly DdqgCategory $category,
        private readonly ProblematicPackageVersionsProvider $source,
        private readonly VersionParser $versionParser,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly PackageIgnoreRuleProvider $packageIgnoreRuleProvider,
    ) {
    }

    /**
     * @param array<string, ConstraintInterface> $packageConstraintMap
     *   The audited set: package name to the exact version (or OR of exact versions) Composer audits
     *
     * @throws PackageVersionsCouldNotBeFetched
     *
     * @return array<string,DdqgFinding>
     *   Package name (e.g., foo/bar) to the finding about it.
     */
    public function __invoke(array $packageConstraintMap): array
    {
        $result = $this->find($packageConstraintMap);

        if ([] === $result) {
            return $result;
        }

        if (null === $this->ignoreRulesByPackage) {
            $this->ignoreRulesByPackage = $this->groupIgnoreRulesByPackage();
        }

        foreach (array_keys($result) as $package_name) {
            foreach ($this->ignoreRulesByPackage[$package_name] ?? [] as $rule) {
                if ($rule->evaluate($package_name, $packageConstraintMap[$package_name])) {
                    $this->eventDispatcher->dispatch(
                        new FindingWasIgnored($result[$package_name], $rule)
                    );
                    unset($result[$package_name]);

                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string, ConstraintInterface> $packageConstraintMap
     *
     * @throws PackageVersionsCouldNotBeFetched
     *
     * @return array<string,DdqgFinding>
     */
    private function find(array $packageConstraintMap): array
    {
        $findings = [];
        foreach ($this->source->findByPackages($packageConstraintMap) as $package_name => $version_range) {
            $package_version_constraint = $this->versionParser->parseConstraints($version_range);
            $audited = $packageConstraintMap[$package_name];
            if ($package_version_constraint->matches($audited)) {
                $offending_version = $this->offendingVersion($audited, $package_version_constraint);
                $findings[$package_name] = new DdqgFinding($this->category, $package_name, $offending_version, $package_version_constraint);
            }
        }

        return $findings;
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

    /**
     * @return array<string,array<PackageIgnoreRule>>
     */
    private function groupIgnoreRulesByPackage(): array
    {
        $rules = $this->packageIgnoreRuleProvider->getIgnoreRules();
        $rules = $rules instanceof \Traversable ? iterator_to_array($rules, false) : $rules;
        $grouped = [];
        foreach ($rules as $rule) {
            $grouped[$rule->getPackageName()][] = $rule;
        }

        return $grouped;
    }
}
