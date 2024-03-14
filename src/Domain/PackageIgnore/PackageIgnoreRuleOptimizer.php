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

namespace mxr576\ddqgComposerAudit\Domain\PackageIgnore;

/**
 * @internal
 */
trait PackageIgnoreRuleOptimizer
{
    /**
     * @return array<string,array<\mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRule>>
     */
    final protected function optimizePackageIgnoreRules(PackageIgnoreRuleProvider $provider): array
    {
        $rules = $provider->getIgnoreRules();
        /** @var \mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRule[] $rules */
        $rules = $rules instanceof \Traversable ? iterator_to_array($rules, false) : $rules;
        if ([] !== $rules) {
            trigger_error("Since 1.1.0 DDQG Composer Audit plugin's ignore features are deprecated, use Composer's built-in audit ignore feature instead. https://getcomposer.org/doc/06-config.md#ignore", E_USER_DEPRECATED);
        }
        /** @var \ArrayObject<string,array<\mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRule>> $tmp */
        $tmp = array_reduce($rules,
            static function (\ArrayObject $carry, PackageIgnoreRule $item) {
                $carry[$item->getPackageName()][] = $item;

                return $carry;
            }, new \ArrayObject());

        return $tmp->getArrayCopy();
    }
}
