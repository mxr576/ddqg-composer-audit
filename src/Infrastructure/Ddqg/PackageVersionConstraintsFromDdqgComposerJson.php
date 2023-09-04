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

use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use loophp\collection\Collection;

/**
 * @internal
 */
final class PackageVersionConstraintsFromDdqgComposerJson
{
    /**
     * @param iterable<string> $json_iterator
     * @param string[] $filter_by_package_names
     *
     * @return array<string,string>
     *    An associative array where key are package names and values are valid
     *    version constraints that can be parsed by VersionParser.
     */
    public static function extract(iterable $json_iterator, array $filter_by_package_names = []): array
    {
        // Extra assert()-s are needed to make PHPStan happy.
        // @see https://github.com/phpstan/phpstan/issues/5927
        return Collection::fromIterable(new Items($json_iterator, ['decoder' => new ExtJsonDecoder(true)]))
          ->filter(static function (mixed $v, mixed $k): bool {
              assert(is_string($k));

              return 'conflict' === $k;
          })
          ->map(
              static function ($value) use ($filter_by_package_names): array {
                  assert(is_array($value));

                  return Collection::fromIterable($value)
                    ->filter(static fn ($value, $key): bool => !([] !== $filter_by_package_names) || in_array($key, $filter_by_package_names, true))
                    ->all(false);
              }
          )
          ->limit(1)
          ->current(0, []);
    }
}
