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

use Composer\Util\HttpDownloader;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\NonDrupal10CompatiblePackageVersionsProvider;

/**
 * @see \mxr576\ddqgComposerAudit\Supportive\Infrastructure\Composer\NonDrupal10CompatiblePackageVersionsProviderFromComposerLock
 *
 *@todo This is currently dead code because always an instance of
 *   NonDrupal10CompatiblePackageVersionsProviderFromComposerLock is used
 *   instead.
 *
 * @internal
 */
final class NonDrupal10CompatiblePackageVersionsFromLatestDdqgBuild extends PackageVersionsFromRemoteComposerJsonRepository implements NonDrupal10CompatiblePackageVersionsProvider
{
    private const LATEST_RELEASE_URL = 'https://raw.githubusercontent.com/mxr576/ddqg/non-d10-compatible-versions/composer.json';

    public function __construct(HttpDownloader $httpDownloader)
    {
        parent::__construct(self::LATEST_RELEASE_URL, $httpDownloader);
    }
}
