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

namespace mxr576\ddqgComposerAudit\Supportive\Adapter\Composer;

use Composer\EventDispatcher\Event as ComposerEvent;
use Composer\EventDispatcher\EventDispatcher as ComposerEventDispatcher;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Event\UnsupportedPackageWasIgnored;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * @internal
 */
final class Psr14EventDispatcherAdapterForComposer implements EventDispatcherInterface
{
    public function __construct(private ComposerEventDispatcher $dispatcher)
    {
    }

    public function dispatch(object $event): object
    {
        if ($event instanceof UnsupportedPackageWasIgnored) {
            $composer_event = UnsupportedPackageWasIgnoredAdapter::createFromWrapped($event);
        } else {
            $composer_event = new ComposerEvent($event::class);
        }

        if ($event instanceof StoppableEventInterface) {
            $composer_event->stopPropagation();
        }
        $this->dispatcher->dispatch($event::class, $composer_event);

        return $event;
    }
}
