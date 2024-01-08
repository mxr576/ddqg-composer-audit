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

namespace mxr576\ddqgComposerAudit\Infrastructure\Ddqg;

use Composer\Util\HttpDownloader;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\InsecurePackageVersionsProvider;

/**
 * @internal
 */
final class InsecurePackageVersionsFromLatestDdqgBuild extends PackageVersionsFromRemoteComposerJsonRepository implements InsecurePackageVersionsProvider
{
    private const LATEST_RELEASE_URL = 'https://raw.githubusercontent.com/mxr576/ddqg/no-insecure-versions/composer.json';

    public function __construct(HttpDownloader $httpDownloader)
    {
        parent::__construct(self::LATEST_RELEASE_URL, $httpDownloader);
    }
}
