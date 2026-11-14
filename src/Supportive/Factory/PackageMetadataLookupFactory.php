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

namespace mxr576\ddqgComposerAudit\Supportive\Factory;

use Composer\Composer;
use mxr576\ddqgComposerAudit\Domain\PackageMetadata\PackageMetadataLookup;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\PackageMetadataLookupFromComposerRepositories;

/**
 * Lets the Presentation layer build the lookup without depending on
 * Infrastructure.
 *
 * @internal
 */
final class PackageMetadataLookupFactory
{
    public function create(Composer $composer): PackageMetadataLookup
    {
        return PackageMetadataLookupFromComposerRepositories::fromInstalledRepositoryAndLocker(
            $composer->getRepositoryManager()->getLocalRepository(),
            $composer->getLocker(),
        );
    }
}
