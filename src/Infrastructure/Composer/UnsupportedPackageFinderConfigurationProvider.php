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

namespace mxr576\ddqgComposerAudit\Infrastructure\Composer;

use Composer\Package\RootPackageInterface;
use Composer\Semver\VersionParser;
use cweagans\Composer\ConfigurablePlugin;
use mxr576\ddqgComposerAudit\Application\PackageFinder\UnsupportedPackageFinderConfigurationProvider as UnsupportedPackageFinderConfigurationProviderContract;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\IgnorePackageByVersionConstraintMatch;

/**
 * @internal
 */
final class UnsupportedPackageFinderConfigurationProvider implements UnsupportedPackageFinderConfigurationProviderContract
{
    private bool $isConfigured = false;

    public function __construct(private readonly RootPackageInterface $rootPackage, private readonly VersionParser $versionParser)
    {
    }

    public function getUnsupportedPackageIgnoreRules(): array
    {
        // Public methods from ConfigurablePlugin MUST NOT be exposed as
        // public APIs of this implementation.
        // We should only build this object once, but if we do in the
        // constructor then it is hard to explain PHPStan what are the
        // available public methods on an anonymous class.
        $configReaderJail = new class() {
            use ConfigurablePlugin;

            public const CONFIG_KEY_IGNORE_UNSUPPORTED_VERSIONS = 'ignore-unsupported-versions';

            public function __construct()
            {
                $this->configuration = [
                  self::CONFIG_KEY_IGNORE_UNSUPPORTED_VERSIONS => [
                    'type' => 'list',
                    'default' => [],
                  ],
                ];
            }
        };

        if (!$this->isConfigured) {
            $configReaderJail->configure($this->rootPackage->getExtra(), 'ddqg-composer-audit');
        }

        $result = [];
        $unsupported_version_ignore_rules = $configReaderJail->getConfig($configReaderJail::CONFIG_KEY_IGNORE_UNSUPPORTED_VERSIONS);
        assert(is_array($unsupported_version_ignore_rules));
        // Check if the source was the environment variable and postprocess
        // the result if needed.
        if (!empty($unsupported_version_ignore_rules) && array_key_exists(0, $unsupported_version_ignore_rules) && str_contains($unsupported_version_ignore_rules[0], ':')) {
            $unsupported_version_ignore_rules = array_reduce($unsupported_version_ignore_rules, static function (array $carry, string $item) {
                [$package, $version] = explode(':', $item);
                $carry[$package] = $version;

                return $carry;
            }, []);
        }
        foreach ($unsupported_version_ignore_rules as $package => $version) {
            // It is a deliberate decision that version ranges are not
            // allowed here. The goal MUST NOT BE silencing a report for the
            // eternity rather making steps to getting rid of the problem.
            $result[] = IgnorePackageByVersionConstraintMatch::fromUserInput($package, $version, $this->versionParser);
        }

        return $result;
    }
}
