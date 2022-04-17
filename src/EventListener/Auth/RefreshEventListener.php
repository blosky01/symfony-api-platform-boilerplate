<?php

namespace App\EventListener\Auth;

use App\Event\AuthEvent;
use Gesdinet\JWTRefreshTokenBundle\Event\RefreshEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RefreshEventListener
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onTokenRefreshed(RefreshEvent $event): void
    {
        $this->eventDispatcher->dispatch(
            new AuthEvent($event),
            AuthEvent::REFRESH_TOKEN_SUCCESS
        );
    }
}
