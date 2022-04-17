# Login With OAuth
Necesitaremos el bundle [knpUOAuth2ClientBundle](https://github.com/knpuniversity/oauth2-client-bundle):

    composer require knpuniversity/oauth2-client-bundle

Integración con un servidor OAuth2 (por ejemplo, Facebook, GitHub) para:

* Autenticación / inicio de sesión 'social'
* Tipo de funcionalidad 'Conectar con Facebook'
* Obtener claves de acceso a través de OAuth2 para usar con una API
* Realización de autenticación OAuth2 con Symfony Custom Authenticator (o Guard Authenticator para aplicaciones heredadas)

## Google Client

Primero añadiremos algunas propiedades extra a nuestra entidad de `User`:

* googleId
* avatar (Opcional)
* hostedDomain (Opcional)
* locale (Opcional)

```php
<?php
#src/Entity/User.php

class User implements UserInterface, PasswordAuthenticatedUserInterface
{

# ...

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $googleId;

    #[ORM\Column(type: 'text', nullable: true)]
    private $avatar;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $hostedDomain;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'es'])]
    private $locale;

# ...

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getHostedDomain(): ?string
    {
        return $this->hostedDomain;
    }

    public function setHostedDomain(?string $hostedDomain): self
    {
        $this->hostedDomain = $hostedDomain;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }
}
```

Configuramos el archivo `knpu_oauth2_client.yaml`:

```yaml
#config/packages/knpu_oauth2_client.yaml

knpu_oauth2_client:
    clients:
        # configure your clients as described here: https://github.com/knpuniversity/oauth2-client-bundle#configuration
        google:
            type: google
            client_id: '%env(resolve:GOOGLE_CLIENT_ID)%'
            client_secret: '%env(resolve:GOOGLE_CLIENT_SECRET)%'
            redirect_route: connect_google_check
            redirect_params: {}
```

Agregamos las variables de entorno en nuestro `.env`:

```env
    GOOGLE_CLIENT_ID= my_google_client_id
    GOOGLE_CLIENT_SECRET= my_google_client_secret
```

Crearemos el controlador `GoogleController` que administrará la conexión de Google:

```php
<?php

# Controller/Auth/GoogleController
namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController
{

    #[Route('/api/auth/connect/google', name: 'connect_google')]
    public function connectAction(ClientRegistry $clientRegistry)
    {
        # Redirect to google
        return $clientRegistry->getClient('google')->redirect([], []);
    }

    /**
     * After going to Google, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     */
    #[Route('/api/auth/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request)
    {
        # if you want to *authenticate* the user, then
        # leave this method blank and create a Guard authenticator
    }
}
```

Añadimos nuestra clase `GoogleAuthenticator` extendiendo de la clase OAuth2Authenticator:

```php
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
        # continue ONLY if the current ROUTE matches the check ROUTE
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
                    $user->setName($googleUser->getFirstName());
                    $user->setSurnames($googleUser->getLastName());
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
        # Our own return logic
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
```

Por último configuraremos nuestro `security.yaml`:

```diff
#config/packages/security.yaml

security:
    # ...
+   enable_authenticator_manager: true

    firewalls:
        main:
            # ...
+           custom_authenticators:
+               - App\Security\GoogleAuthenticator
```


« [User Logout](./UserLogout.md) • [Login Throttling](./LoginThrottling.md) »