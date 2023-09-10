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
use mxr576\ddqgComposerAudit\Application\PackageFinder\Exception\UnexpectedPackageFinderException;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\InsecurePackageVersionsProvider;
use mxr576\ddqgComposerAudit\Domain\SecurityAdvisory\SecurityAdvisoryFinder;
use mxr576\ddqgComposerAudit\Domain\SecurityAdvisory\SecurityAdvisoryFinderFromProblematicPackageProvider;
use mxr576\ddqgComposerAudit\Domain\SecurityAdvisory\UnexpectedSecurityAdvisoryFinderException;

/**
 * @internal
 */
final class FindInsecurePackages implements PackageFinder
{
    private readonly SecurityAdvisoryFinder $securityAdvisoryFinder;

    /**
     * Constructs a new object.
     */
    public function __construct(
        InsecurePackageVersionsProvider $insecurePackageVersionsProvider,
        VersionParser $versionParser,
    ) {
        $this->securityAdvisoryFinder = new SecurityAdvisoryFinderFromProblematicPackageProvider($insecurePackageVersionsProvider, $versionParser);
    }

    public function __invoke(array $packageConstraintMap): array
    {
        // We have no additional use case for this feature, yet.
        try {
            return $this->securityAdvisoryFinder->find($packageConstraintMap);
        } catch (UnexpectedSecurityAdvisoryFinderException $e) {
            throw new UnexpectedPackageFinderException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
