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

namespace mxr576\ddqgComposerAudit\Supportive\Adapter\Composer;

use Composer\Advisory\SecurityAdvisory;
use Composer\EventDispatcher\Event;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Event\AdvisoryWasIgnored;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRule;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
final class AdvisoryWasIgnoredAdapter extends Event
{
    /**
     * Constructs a new object.
     *
     * @param SecurityAdvisory[] $advisories
     */
    public function __construct(
        public readonly string $category,
        public readonly string $packageName,
        public readonly PackageIgnoreRule $ignoreRule,
        public readonly array $advisories,
    ) {
        Assert::allIsInstanceOf($this->advisories, SecurityAdvisory::class);
        parent::__construct(self::class);
    }

    public static function createFromWrapped(AdvisoryWasIgnored $event): self
    {
        return new self($event->category->type(), $event->packageName, $event->ignoreRule, $event->advisories);
    }
}
