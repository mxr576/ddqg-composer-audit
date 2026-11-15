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

namespace mxr576\ddqgComposerAudit\Domain\PackageIgnore;

/**
 * @internal
 */
trait PackageIgnoreRuleOptimizer
{
    /**
     * @return array<string,array<PackageIgnoreRule>>
     */
    final protected function optimizePackageIgnoreRules(PackageIgnoreRuleProvider $provider): array
    {
        $rules = $provider->getIgnoreRules();
        /** @var PackageIgnoreRule[] $rules */
        $rules = $rules instanceof \Traversable ? iterator_to_array($rules, false) : $rules;
        /** @var array<string,array<PackageIgnoreRule>> $tmp */
        $tmp = [];
        foreach ($rules as $item) {
            $tmp[$item->getPackageName()][] = $item;
        }

        return $tmp;
    }
}
