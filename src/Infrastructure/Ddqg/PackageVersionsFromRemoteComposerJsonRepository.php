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

namespace mxr576\ddqgComposerAudit\Infrastructure\Ddqg;

use Composer\Downloader\TransportException;
use Composer\Util\HttpDownloader;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use loophp\collection\Collection;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\Exception\PackageVersionsCouldNotBeFetched;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider;

/**
 * @internal
 */
abstract class PackageVersionsFromRemoteComposerJsonRepository implements ProblematicPackageVersionsProvider
{
    public function __construct(private readonly string $url, private readonly HttpDownloader $httpDownloader)
    {
    }

    /**
     * {@inheritDoc}
     */
    final public function findByPackages(string ...$package_names): array
    {
        // Streaming the response would be good, but using native Composer tools
        // is better instead of depending on new 3rd party packages that may
        // conflict with project dependencies.
        try {
            $composer_json = $this->httpDownloader->get($this->url)->getBody();
        } catch (TransportException $e) {
            throw new PackageVersionsCouldNotBeFetched(sprintf('Failed to fetch package information from "%s". Reason: %s', $this->url, $e->getMessage()), $e->getCode(), $e);
        }

        assert(null !== $composer_json);

        // Extra assert()-s are needed to make PHPStan happy.
        // @see https://github.com/phpstan/phpstan/issues/5927
        return Collection::fromIterable(Items::fromString($composer_json, ['decoder' => new ExtJsonDecoder(true)]))
          ->filter(static function (mixed $v, mixed $k): bool {
              assert(is_string($k));

              return 'conflict' === $k;
          })
          ->map(
              static function ($value) use ($package_names): array {
                  assert(is_array($value));

                  return Collection::fromIterable($value)
                    ->filter(static fn ($value, $key): bool => in_array($key,
                        $package_names, true))
                    ->all(false);
              }
          )
          ->limit(1)
          ->current(0, []);
    }
}
