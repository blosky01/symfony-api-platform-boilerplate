# User Password Reset

### Paso 1: Descargar el Bundle

```console
composer require symfonycasts/reset-password-bundle
```

Este paquete proporciona una solución segura lista para usar que permite a los usuarios restablecer sus contraseñas olvidadas.

### Paso 2: Creamos el controlador

```php
<?php

namespace App\Controller\Auth;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[AsController]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    private $resetPasswordHelper;
    private $entityManager;

    public function __construct(
        ResetPasswordHelperInterface $resetPasswordHelper,
        EntityManagerInterface $entityManager
    )
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/auth/reset-password/send-email', name: 'app_check-email', methods: ['POST'])]
    public function request(Request $request, MailerInterface $mailer): Response
    {
        $content = json_decode($request->getContent());

        if (!$content || empty($content->email)) {

            $response = new JsonResponse(['message' => 'Email not found.'], Response::HTTP_UNAUTHORIZED);

            return $response;
        }

        try {
            $this->processSendingPasswordResetEmail(
                $content->email,
                $mailer
            );
        } catch (\Throwable $th) {
            $response = new JsonResponse(['message' => 'Email sended.'], Response::HTTP_OK);

            return $response;
        }

        $response = new JsonResponse(['message' => 'Email sended.'], Response::HTTP_OK);

        return $response;
    }

    #[Route('/api/auth/reset-password/reset', name: 'app_reset_password', methods: ['POST'])]
    public function reset(Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $content = json_decode($request->getContent());

        if (!$content || empty($content->password)) {
            $response = new JsonResponse(['message' => 'Password not found.'], Response::HTTP_UNAUTHORIZED);

            return $response;
        }

        if (empty($content->token)) {
            $response = new JsonResponse(['message' => 'Token not found.'], Response::HTTP_UNAUTHORIZED);

            return $response;
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($content->token);
        } catch (ResetPasswordExceptionInterface $e) {
            $response = new JsonResponse(['message' => $e->getReason()], Response::HTTP_UNAUTHORIZED);

            return $response;
        }

        $this->resetPasswordHelper->removeResetRequest($content->token);

        $encodedPassword = $userPasswordHasher->hashPassword(
            $user,
            $content->password
        );

        $user->setPassword($encodedPassword);
        $this->entityManager->flush();

        $response = new JsonResponse(['message' => 'Password reset successfully'], Response::HTTP_OK);

        return $response;
       
    }

    #[Route('/api/auth/reset-password/check-token', name: 'is-valid-token', methods: ['POST'])]
    public function validateToken(Request $request): Response
    {
        $content = json_decode($request->getContent());

        if (!$content || empty($content->token)) {
            $response = new JsonResponse(['message' => 'Token not found.'], Response::HTTP_UNAUTHORIZED);

            return $response;
        }

        try {
            $this->resetPasswordHelper->validateTokenAndFetchUser($content->token);
        } catch (ResetPasswordExceptionInterface $e) {
            $response = new JsonResponse(['message' => $e->getReason()], Response::HTTP_UNAUTHORIZED);

            return $response;
        }

        $response = new JsonResponse(['message' => 'Valid token!'], Response::HTTP_OK);

        return $response;
    }

    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer): bool | Response | Exception
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        if (!$user) {
            throw new Exception('User not Found.');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            throw new Exception($e->getReason());
        }

        if ($resetToken) {
            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@example.com', 'Reset Password'))
                ->to($user->getEmail())
                ->subject('Your password reset request')
                ->htmlTemplate('reset_password/email.html.twig')
                ->context([
                    'resetToken' => $resetToken,
                ])
            ;

            $mailer->send($email);

            $this->setTokenObjectInSession($resetToken);
        }

        return true;
    }

}
```

« [User Email Verify](./UserEmailVerify.md) • [JWT Login](./JWTLogin.md) »