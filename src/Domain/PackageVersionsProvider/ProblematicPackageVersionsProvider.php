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
     * @phpstan-return array<string,string>
     *
     * @throws \mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\PackageVersionsCouldNotBeFetched
     */
    public function findByPackages(string ...$package_names): array;
}
