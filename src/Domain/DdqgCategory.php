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

namespace mxr576\ddqgComposerAudit\Domain;

/**
 * One kind of problem DDQG reports.
 *
 * A category decides the type, the title and whether the ID of its advisories
 * contains the installed version.
 */
enum DdqgCategory: string
{
    case Insecure = 'insecure';
    case Unsupported = 'unsupported';
    case Deprecated = 'deprecated';
    case D10Incompatible = 'D10-incompatible';

    /**
     * The type token in the advisory ID.
     */
    public function type(): string
    {
        return $this->value;
    }

    public function title(string $installedVersion): string
    {
        return match ($this) {
            self::Insecure => sprintf('The installed "%s" version is insecure.', $installedVersion),
            self::Unsupported => sprintf('The installed "%s" version is unsupported.', $installedVersion),
            self::Deprecated => sprintf('The installed "%s" version is deprecated.', $installedVersion),
            self::D10Incompatible => sprintf('The installed "%s" version is not compatible with Drupal 10.', $installedVersion),
        };
    }

    /**
     * Whether the advisory ID contains the installed version.
     */
    public function idIncludesVersion(): bool
    {
        return match ($this) {
            self::Unsupported, self::Deprecated => true,
            self::Insecure, self::D10Incompatible => false,
        };
    }
}
