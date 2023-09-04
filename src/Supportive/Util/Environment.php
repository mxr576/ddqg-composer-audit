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

namespace mxr576\ddqgComposerAudit\Supportive\Util;

use Composer\Util\Platform;

/**
 * @internal
 */
final class Environment
{
    public static function isTestEnvironment(): bool
    {
        return false !== Platform::getEnv('DDQG_COMPOSER_AUDIT_TEST_ENV');
    }
}
