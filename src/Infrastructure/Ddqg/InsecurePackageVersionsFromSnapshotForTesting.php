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
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\InsecurePackageVersionsProvider;

/**
 * @internal
 */
final class InsecurePackageVersionsFromSnapshotForTesting implements InsecurePackageVersionsProvider
{
    public function findByPackages(array $packageConstraintMap): array
    {
        return PackageVersionConstraintsFromDdqgComposerJson::extract(new FileChunks(__DIR__ . '/fixtures/insecure-composer-2023-04-20.json'), array_keys($packageConstraintMap));
    }
}
