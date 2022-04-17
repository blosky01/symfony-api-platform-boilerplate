<?php

namespace App\EventListener\Auth;

use App\Event\AuthEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LogoutEventListener
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $event)
    {
        $response = new JsonResponse(
            [
                'code' => 200,
                'message' => 'The supplied tokens has been invalidated.',
            ],
            JsonResponse::HTTP_OK
        );
        $response->headers->clearCookie('jwt_hp', '/', null);
        $response->headers->clearCookie('jwt_s', '/', null);
        $response->headers->clearCookie('refreshToken', '/', null);

        $this->eventDispatcher->dispatch(
            new AuthEvent($event),
            AuthEvent::LOGOUT_SUCCESS
        );

        $event->setResponse($response);
    }
}