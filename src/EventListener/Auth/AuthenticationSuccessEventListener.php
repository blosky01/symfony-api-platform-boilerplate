<?php

namespace App\EventListener\Auth;

use App\Event\AuthEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AuthenticationSuccessEventListener
{
    const REMEMBER_ME_EXPIRATION_DAYS = 30;
    const COOKIES = [
        'jwt_hp',
        'jwt_s'
    ];

    private RequestStack $requestStack;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $content = json_decode($request->getContent(), true);

        if (!empty($content['remember']) && $content['remember']) {
            $this->updateCookies($event->getResponse());
        }

        $this->eventDispatcher->dispatch(
            new AuthEvent($event),
            AuthEvent::AUTHENTICATION_SUCCESS
        );
    }

    public function updateCookies(Response $response): void
    {
        $expiration = (new \DateTime('+' . self::REMEMBER_ME_EXPIRATION_DAYS . ' days'))->getTimestamp();
        $cookies = array_filter($response->headers->getCookies(), fn($cookie) => in_array($cookie->getName(), self::COOKIES));
     
        foreach ($cookies as $cookie) {
            $newCookie = new Cookie(
                $cookie->getName(),
                $cookie->getValue(),
                $expiration,
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly(),
                false,
                $cookie->getSameSite()
            );
            $response->headers->setCookie($newCookie);
        }
    }

}
