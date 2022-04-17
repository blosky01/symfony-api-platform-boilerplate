# User Email Verify

### Paso 1: Descargar el Bundle

```console
composer require symfonycasts/verify-email-bundle
```
VerifyEmailBundle genera, y valida, una URL segura y firmada que se puede enviar por correo electrónico a los usuarios para confirmar su dirección de correo electrónico. Lo hace sin necesidad de almacenamiento, por lo que puede usar sus entidades existentes con modificaciones menores. Este paquete proporciona:

Un generador para crear una URL firmada que debe enviarse por correo electrónico al usuario.
Un validador de URL firmado.
Tranquilidad sabiendo que esto se hace sin filtrar la dirección de correo electrónico del usuario en los registros de su servidor (evitando problemas de PII).

### Paso 2: Creamos el controlador

```php
<?php
#src/Controller/Auth/RegistrationConfirmation.php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Twig\Environment;

/**
 * @method User|null getUser()
 */
class RegistrationConfirmation extends AbstractController
{
    /**
     * TODO: Cambiar salt por algo más seguro
     */
    const SALT = '8h9fdjvdfko2km32nibidafvbadfvbdva';
    private $verifyEmailHelper;
    private $entityManager;
    private $twig;
    private $mailer;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        VerifyEmailHelperInterface $helper,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        Environment $twig
    ) {
        $this->verifyEmailHelper = $helper;
        $this->entityManager = $entityManager;
        $this->twig = $twig;
        $this->mailer = $mailer;
    }

    #[Route(
        name: 'api_auth_verify_email',
        path: '/api/auth/verification_email/verify'
    )]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $decryptedIdRaw = base64_decode($request->get('extra'));
        $id = preg_replace(sprintf('/%s/', $this::SALT), '', $decryptedIdRaw);

        if (!$id) {
            $response = new JsonResponse('User ID not found.', JsonResponse::HTTP_UNAUTHORIZED);

            return $response;
        }

        $user = null;

        if (is_string($id) && (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id) === 1)) {
            $user = $userRepository->find($id);
        }        

        if (!$user) {

            $response = new JsonResponse('User not found.', JsonResponse::HTTP_UNAUTHORIZED);

            return $response;
        }

        if ($user->getEmailVerify()) {

            $response = new JsonResponse('User is verified yet.', JsonResponse::HTTP_UNAUTHORIZED);

            return $response;
        }

        try {
            $uri = $request->getUri();
            $userID = $user->getId();
            $userEmail = $user->getEmail();
            $this->verifyEmailHelper->validateEmailConfirmation($uri, $userID, $userEmail);
        } catch (VerifyEmailExceptionInterface $e) {

            $response = new JsonResponse($e->getReason(), JsonResponse::HTTP_UNAUTHORIZED);

            return $response;
        }

        $user->setEmailVerify(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $response = new JsonResponse(['message' => 'Your e-mail address has been verified.'], Response::HTTP_OK);

        return $response;
    }

    #[Route(
        path: '/api/auth/verification_email/resend'
    )]
    public function resendEmail()
    {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        if ($user->getEmailVerify() === true) {
            return new JsonResponse(['message' => 'User is verified yet.'], Response::HTTP_OK);
        }

        $this->sendVerificationEmail($user);

        return new JsonResponse(['message' => 'The verification email is in your mail.'], Response::HTTP_OK);
    }

    public function sendVerificationEmail(User $data)
    {

        $encryptedId = base64_encode($data->getId() . $this::SALT);

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'api_auth_verify_email',
            $data->getId(),
            $data->getEmail(),
            ['extra' => $encryptedId]
        );

        $message = (new Email())
            ->from('no-reply@example.com')
            ->to($data->getEmail())
            ->subject('Email confirmation')
            ->html($this->twig->render(
                'confirmation_email/email.html.twig',
                [
                    'signedUrl' => $signatureComponents->getSignedUrl(),
                    'expiresAt' => $signatureComponents->getexpiresAt(),
                ]
            ));

        if (0 === $this->mailer->send($message)) {
            throw new NotFoundHttpException('Unable to send email');
        }
    }
}

```

### Paso 2: Creamos Event Subscriber - UserCreateEventSubscriber

Este va a suscribirse al evento de API Platform `EventPriorities::POST_WRITE` y va al método `sendVerificationEmail` en caso de que la entidad escrita sea `User` y el método sea POST:

```php
<?php
#src/EventSubscriber/UserCreateEventSubscriber.php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Controller\Auth\RegistrationConfirmation;
use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class UserCreateEventSubscriber implements EventSubscriberInterface
{
    private RegistrationConfirmation $registerVerify;

    public function __construct(
        RegistrationConfirmation $registerVerify
    ) {
        $this->registerVerify = $registerVerify;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['execute', EventPriorities::POST_WRITE],
        ];
    }
    
    public function execute(ViewEvent $event): void
    {
        $user = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$user instanceof User || Request::METHOD_POST !== $method) {
            return;
        }

        $this->registerVerify->sendVerificationEmail($user);
    }
}
```

### Paso 2: Configurar UserCreateEventSubscriber en services.yaml

Configuramos nuestro Event Subscriber `UserCreateEventSubscriber` en `config/services.yaml`:

```yaml
#config/services.yaml

parameters:
    #...
services:
    #...
    App\EventSubscriber\UserCreateEventSubscriber:
        class: App\EventSubscriber\UserCreateEventSubscriber
        tags:
            - { name: kernel.event_subscriber }
```

« [Hash Password](./HashPassword.md) • [User Password Reset](./UserPasswordReset.md) »