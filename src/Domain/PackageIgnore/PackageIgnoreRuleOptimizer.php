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
        $ignore_package_by_present_version_of_other_package_counter = 0;
        /** @var array<string,array<PackageIgnoreRule>> $tmp */
        $tmp = [];
        foreach ($rules as $item) {
            // This type of ignore rule can be only created programmatically at
            // this moment, so we can assume it was added by the package
            // maintainer(s).
            if ($item instanceof IgnorePackageByPresentVersionOfOtherPackage) {
                ++$ignore_package_by_present_version_of_other_package_counter;
            }

            $tmp[$item->getPackageName()][] = $item;
        }

        if (count($tmp) !== $ignore_package_by_present_version_of_other_package_counter) {
            trigger_error("Since 1.1.0 DDQG Composer Audit plugin's ignore features are deprecated, use Composer's built-in audit ignore feature instead. https://getcomposer.org/doc/06-config.md#ignore", E_USER_DEPRECATED);
        }

        return $tmp;
    }
}
