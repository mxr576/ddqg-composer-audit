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

namespace mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider;

interface ProblematicPackageVersionsProvider
{
    /**
     * @throws \mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\Exception\PackageVersionsCouldNotBeFetched
     *
     * @return array<string,string>
     *   An associative array where key are package names and values are valid
     *   version constraints that can be parsed by VersionParser.
     *
     * @see \Composer\Semver\VersionParser::parseConstraints()
     */
    public function findByPackages(string ...$package_names): array;
}
