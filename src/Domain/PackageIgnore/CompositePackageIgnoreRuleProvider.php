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
final class CompositePackageIgnoreRuleProvider implements PackageIgnoreRuleProvider
{
    /**
     * @var \mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRuleProvider[]
     */
    private readonly array $providers;

    public function __construct(PackageIgnoreRuleProvider ...$providers)
    {
        $this->providers = $providers;
    }

    public function getIgnoreRules(): iterable
    {
        foreach ($this->providers as $provider) {
            yield from $provider->getIgnoreRules();
        }
    }
}
