<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Dezső Biczó
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
use mxr576\ddqgComposerAudit\Application\PackageFinder\NonDrupal10CompatiblePackageFinderConfigurationProvider as NonDrupal10CompatiblePackageVersionsProviderContract;

/**
 * @internal
 */
final class NonDrupal10CompatiblePackageFinderConfigurationProvider implements NonDrupal10CompatiblePackageVersionsProviderContract
{
    private bool $isConfigured = false;

    public function __construct(private readonly RootPackageInterface $rootPackage)
    {
    }

    public function isEnabled(): bool
    {
        // Public methods from ConfigurablePlugin MUST NOT be exposed as
        // public APIs of this implementation.
        // We should only build this object once, but if we do in the
        // constructor then it is hard to explain PHPStan what are the
        // available public methods on an anonymous class.
        $configReaderJail = new class() {
            use ConfigurablePlugin;

            public const CONFIG_KEY_CHECK_D10_COMPATIBILITY = 'check-d10-compatibility';

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

        if (!$this->isConfigured) {
            $configReaderJail->configure($this->rootPackage->getExtra(), 'ddqg-composer-audit');
        }

        $result = $configReaderJail->getConfig($configReaderJail::CONFIG_KEY_CHECK_D10_COMPATIBILITY);
        assert(is_bool($result));

        return $result;
    }
}
