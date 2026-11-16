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

namespace mxr576\ddqgComposerAudit\Presentation\Composer\Event;

use Composer\EventDispatcher\Event as ComposerEvent;
use Composer\EventDispatcher\EventDispatcher as ComposerEventDispatcher;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Event\AdvisoryWasIgnored;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * @internal
 */
final class Psr14EventDispatcherAdapterForComposer implements EventDispatcherInterface
{
    public function __construct(private readonly ComposerEventDispatcher $dispatcher)
    {
    }

    public function dispatch(object $event): object
    {
        // PSR-14: an already stopped event must not reach any listener. Composer only checks its stop
        // flag after a listener ran, so copying the flag across would still call the first listener.
        // Stopping by a listener is not reflected back on the PSR event.
        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return $event;
        }

        if ($event instanceof AdvisoryWasIgnored) {
            $composer_event = new AdvisoryWasIgnoredAdapter($event);
        } else {
            $composer_event = new ComposerEvent($event::class);
        }

        $this->dispatcher->dispatch($event::class, $composer_event);

        return $event;
    }
}
