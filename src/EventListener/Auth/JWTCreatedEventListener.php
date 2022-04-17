<?php

namespace App\EventListener\Auth;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTCreatedEventListener
{
    const REMEMBER_ME_EXPIRATION_DAYS = 30;
    
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $content = json_decode($request->getContent(), true);
        $payload = $event->getData();
        
        if (!empty($content['remember']) && $content['remember']) {
            $payload['exp'] = (new \DateTime('+' . self::REMEMBER_ME_EXPIRATION_DAYS . ' days'))->getTimestamp();
        }

        $payload['ip'] = $request->getClientIp();
        $event->setData($payload);
    }
}