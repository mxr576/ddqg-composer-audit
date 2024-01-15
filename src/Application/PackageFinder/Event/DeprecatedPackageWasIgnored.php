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

namespace mxr576\ddqgComposerAudit\Application\PackageFinder\Event;

use Composer\Advisory\SecurityAdvisory;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRule;
use Webmozart\Assert\Assert;

final class DeprecatedPackageWasIgnored
{
    /**
     * Constructs a new object.
     *
     * @param \Composer\Advisory\SecurityAdvisory[] $advisories
     */
    public function __construct(
        public readonly string $packageName,
        public readonly PackageIgnoreRule $ignoreRule,
        public readonly array $advisories,
    ) {
        Assert::allIsInstanceOf($this->advisories, SecurityAdvisory::class);
    }
}
