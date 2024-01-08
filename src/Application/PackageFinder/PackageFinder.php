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

interface PackageFinder
{
    /**
     * @param array<string, \Composer\Semver\Constraint\ConstraintInterface> $packageConstraintMap
     *   Map of package name to constraint (can be MatchAllConstraint to fetch
     *   all advisories)
     *
     * @throws \mxr576\ddqgComposerAudit\Application\PackageFinder\Exception\UnexpectedPackageFinderException
     *
     * @return array<string,\Composer\Advisory\SecurityAdvisory[]>
     *   An associative array where key are package names (e.g, foo/bar) and
     *   values are security advisories for the given package.
     */
    public function __invoke(array $packageConstraintMap): array;
}
