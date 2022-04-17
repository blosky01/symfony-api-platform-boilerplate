<?php

namespace App\Security;

use App\Entity\User;
use App\Event\AuthEvent;
use League\OAuth2\Client\Provider\GoogleUser;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class GoogleAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private AuthenticationSuccessHandler $authenticationSuccessHandler;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        AuthenticationSuccessHandler $authenticationSuccessHandler,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->authenticationSuccessHandler = $authenticationSuccessHandler;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function supports(Request $request): ?bool
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleUser->getId()]);

                if (!$user) {
                    $user = new User();
                    $user->setEmail($googleUser->getEmail());
                    $user->setFirstName($googleUser->getFirstName());
                    $user->setLastName($googleUser->getLastName());
                    $user->setUsername($googleUser->getName());
                    $user->setGoogleId($googleUser->getId());
                    $user->setHostedDomain($googleUser->getHostedDomain());
                    $user->setHostedDomain($googleUser->getHostedDomain());
                    $user->setLocale($googleUser->getLocale());
                    $user->setAvatar($googleUser->getAvatar());
                    $this->entityManager->persist($user);

                    $this->eventDispatcher->dispatch(
                        new AuthEvent($user),
                        AuthEvent::REGISTERED_SUCCESS
                    );
                }
                
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /**
         * Our own return logic
        */
        $user = $token->getUser();
        $response = $this->authenticationSuccessHandler->handleAuthenticationSuccess($user);
        $cookies = $response->headers->getCookies();
        $response = new RedirectResponse('/api');
        array_map(fn($cookie) => $response->headers->setcookie($cookie), $cookies);

        return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

//    public function start(Request $request, AuthenticationException $authException = null): Response
//    {
//        /*
//         * If you would like this class to control what happens when an anonymous user accesses a
//         * protected page (e.g. redirect to /login), uncomment this method and make this class
//         * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
//         *
//         * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
//         */
//    }
}