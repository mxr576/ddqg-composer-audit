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

/**
 * The deprecation notice for the plugin's own ignore settings.
 *
 * Remove together with those settings in 2.0.0.
 *
 * @internal
 */
final class IgnoreSettingsDeprecationNotice
{
    public static function trigger(): void
    {
        trigger_error("Since 1.1.0 DDQG Composer Audit plugin's ignore features are deprecated, use Composer's built-in audit ignore feature instead. https://getcomposer.org/doc/06-config.md#ignore", E_USER_DEPRECATED);
    }
}
