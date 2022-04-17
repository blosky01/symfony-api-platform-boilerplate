<?php

namespace App\EventListener\Auth;

use App\Event\AuthEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AuthenticationFailureEventListener
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onAuthenticationFailureResponse(AuthenticationFailureEvent $event)
    {
        // $response = new JWTAuthenticationFailureResponse('Bad credentials, please verify that your username/password are correctly set', JsonResponse::HTTP_UNAUTHORIZED);

        $this->eventDispatcher->dispatch(
            new AuthEvent($event),
            AuthEvent::AUTHENTICATION_FAILURE
        );

        // $event->setResponse($response);
    }

}
