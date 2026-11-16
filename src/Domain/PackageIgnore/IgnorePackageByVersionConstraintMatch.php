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

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\VersionParser;

/**
 * @internal
 */
final readonly class IgnorePackageByVersionConstraintMatch implements PackageIgnoreRule
{
    public function __construct(private string $packageName, private ConstraintInterface $rule)
    {
    }

    public static function fromUserInput(string $packageName, string $version, ?VersionParser $parser = null): self
    {
        $parser ??= new VersionParser();

        return new self($packageName, new Constraint(Constraint::STR_OP_EQ, $parser->normalize($version)));
    }

    public function evaluate(string $packageName, ConstraintInterface $version): bool
    {
        return $this->packageName === $packageName && $this->rule->matches($version);
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }
}
