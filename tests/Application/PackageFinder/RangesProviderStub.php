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

namespace mxr576\ddqgComposerAudit\Tests\Application\PackageFinder;

use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\Exception\PackageVersionsCouldNotBeFetched;

/**
 * Implements a DDQG source that serves fixed ranges or fails to fetch.
 *
 * Use it in anonymous classes implementing a provider interface.
 */
trait RangesProviderStub
{
    /**
     * @param array<string,string> $ranges
     */
    public function __construct(private readonly array $ranges, private readonly bool $fails = false)
    {
    }

    /**
     * @throws PackageVersionsCouldNotBeFetched
     */
    public function findByPackages(array $packageConstraintMap): array
    {
        if ($this->fails) {
            throw new PackageVersionsCouldNotBeFetched('DDQG could not be fetched.');
        }

        return array_intersect_key($this->ranges, array_flip(array_keys($packageConstraintMap)));
    }
}
