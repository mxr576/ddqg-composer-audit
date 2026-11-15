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

namespace mxr576\ddqgComposerAudit\Presentation\Composer\Event;

use Composer\EventDispatcher\Event;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Event\AdvisoryWasIgnored;

/**
 * @internal
 */
final class AdvisoryWasIgnoredAdapter extends Event
{
    public function __construct(public readonly AdvisoryWasIgnored $wrapped)
    {
        parent::__construct(self::class);
    }
}
