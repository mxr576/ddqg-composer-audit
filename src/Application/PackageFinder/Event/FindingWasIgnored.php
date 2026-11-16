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

namespace mxr576\ddqgComposerAudit\Application\PackageFinder\Event;

use mxr576\ddqgComposerAudit\Domain\Finding\DdqgFinding;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRule;

final class FindingWasIgnored
{
    public function __construct(
        public readonly DdqgFinding $finding,
        public readonly PackageIgnoreRule $ignoreRule,
    ) {
    }
}
