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

namespace mxr576\ddqgComposerAudit\Infrastructure\Ddqg;

use JsonMachine\FileChunks;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider;

/**
 * Serves the problematic package versions of a DDQG branch from a snapshot
 * taken for testing.
 *
 * @internal
 */
final class PackageVersionsFromSnapshotForTesting implements ProblematicPackageVersionsProvider
{
    private readonly string $file;

    public function __construct(DdqgBranch $branch)
    {
        $this->file = __DIR__ . '/fixtures/' . match ($branch) {
            DdqgBranch::Insecure => 'insecure-composer-2023-04-20.json',
            DdqgBranch::Unsupported => 'unsupported-composer-2023-04-29.json',
            DdqgBranch::Deprecated => 'deprecated-composer-2024-01-08.json',
        };
    }

    public function findByPackages(array $packageConstraintMap): array
    {
        return PackageVersionConstraintsFromDdqgComposerJson::extract(new FileChunks($this->file), array_keys($packageConstraintMap));
    }
}
