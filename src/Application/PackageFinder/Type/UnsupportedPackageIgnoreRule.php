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

namespace mxr576\ddqgComposerAudit\Application\PackageFinder\Type;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\VersionParser;

final class UnsupportedPackageIgnoreRule
{
    public function __construct(public readonly string $packageName, public readonly ConstraintInterface $rule)
    {
    }

    public static function ignorePackageVersion(string $packageName, string $version, VersionParser $parser = null): self
    {
        $parser = $parser ?? new VersionParser();

        return new self($packageName, new Constraint(Constraint::STR_OP_EQ, $parser->normalize($version)));
    }
}
