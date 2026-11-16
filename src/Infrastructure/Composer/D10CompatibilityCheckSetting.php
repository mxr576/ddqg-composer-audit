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

namespace mxr576\ddqgComposerAudit\Infrastructure\Composer;

use Composer\Package\RootPackageInterface;
use cweagans\Composer\ConfigurablePlugin;

/**
 * @internal
 */
final readonly class D10CompatibilityCheckSetting
{
    public function __construct(private RootPackageInterface $rootPackage)
    {
    }

    public function isEnabled(): bool
    {
        // Public methods from ConfigurablePlugin MUST NOT be exposed as
        // public APIs of this implementation, so an anonymous class jails
        // them.
        $configReaderJail = new class {
            use ConfigurablePlugin;

            public const string CONFIG_KEY_CHECK_D10_COMPATIBILITY = 'check-d10-compatibility';

            public function __construct()
            {
                $this->configuration = [
                    self::CONFIG_KEY_CHECK_D10_COMPATIBILITY => [
                        'type' => 'bool',
                        'default' => false,
                    ],
                ];
            }
        };

        $configReaderJail->configure($this->rootPackage->getExtra(), 'ddqg-composer-audit');

        $result = $configReaderJail->getConfig($configReaderJail::CONFIG_KEY_CHECK_D10_COMPATIBILITY);
        assert(is_bool($result));

        return $result;
    }
}
