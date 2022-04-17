<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Event\AuthEvent;
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
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[AsController]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    private $resetPasswordHelper;
    private $entityManager;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ResetPasswordHelperInterface $resetPasswordHelper,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    #[Route('/api/auth/reset-password/send-email', name: 'app_check-email', methods: ['POST'])]
    public function request(Request $request, MailerInterface $mailer): Response
    {
        $content = json_decode($request->getContent());

        if (!$content || empty($content->email)) {

            $response = new JsonResponse(['message' => 'Email not found.'], Response::HTTP_UNAUTHORIZED);

            $this->eventDispatcher->dispatch(
                new AuthEvent([
                    'response' => $response,
                    'email' => ''
                ]),
                AuthEvent::RESET_PASSWORD_EMAIL_SENDED_FAILURE
            );

            return $response;
        }

        try {
            $this->processSendingPasswordResetEmail(
                $content->email,
                $mailer
            );
        } catch (\Throwable $th) {
            $response = new JsonResponse(['message' => $th->getMessage()], Response::HTTP_UNAUTHORIZED);

            $this->eventDispatcher->dispatch(
                new AuthEvent([
                    'response' => $response,
                    'email' => $content->email
                ]),
                AuthEvent::RESET_PASSWORD_EMAIL_SENDED_FAILURE
            );

            $response = new JsonResponse(['message' => 'Email sended.'], Response::HTTP_OK);
            return $response;
        }

        $response = new JsonResponse(['message' => 'Email sended.'], Response::HTTP_OK);

        $this->eventDispatcher->dispatch(
            new AuthEvent([
                'response' => $response,
                'email' => $content->email
            ]),
            AuthEvent::RESET_PASSWORD_EMAIL_SENDED_SUCCESS
        );

        return $response;
    }

    #[Route('/api/auth/reset-password/reset', name: 'app_reset_password', methods: ['POST'])]
    public function reset(Request $request, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $content = json_decode($request->getContent());

        if (!$content || empty($content->password)) {
            $response = new JsonResponse(['message' => 'Password not found.'], Response::HTTP_UNAUTHORIZED);

            $this->eventDispatcher->dispatch(
                new AuthEvent([
                    'response' => $response
                ]),
                AuthEvent::RESET_PASSWORD_FAILURE
            );

            return $response;
        }

        if (empty($content->token)) {
            $response = new JsonResponse(['message' => 'Token not found.'], Response::HTTP_UNAUTHORIZED);

            $this->eventDispatcher->dispatch(
                new AuthEvent([
                    'response' => $response
                ]),
                AuthEvent::RESET_PASSWORD_FAILURE
            );

            return $response;
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($content->token);
        } catch (ResetPasswordExceptionInterface $e) {
            $response = new JsonResponse(['message' => $e->getReason()], Response::HTTP_UNAUTHORIZED);

            $this->eventDispatcher->dispatch(
                new AuthEvent([
                    'response' => $response
                ]),
                AuthEvent::RESET_PASSWORD_FAILURE
            );

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

        $this->eventDispatcher->dispatch(
            new AuthEvent([
                'response' => $response,
                'user' => $user
            ]),
            AuthEvent::RESET_PASSWORD_SUCCESS
        );

        return $response;
       
    }

    #[Route('/api/auth/reset-password/check-token', name: 'is-valid-token', methods: ['POST'])]
    public function validateToken(Request $request): Response
    {
        $content = json_decode($request->getContent());

        if (!$content || empty($content->token)) {
            $response = new JsonResponse(['message' => 'Token not found.'], Response::HTTP_UNAUTHORIZED);

            $this->eventDispatcher->dispatch(
                new AuthEvent([
                    'response' => $response
                ]),
                AuthEvent::RESET_PASSWORD_CHECK_TOKEN_FAILURE
            );

            return $response;
        }

        try {
            $this->resetPasswordHelper->validateTokenAndFetchUser($content->token);
        } catch (ResetPasswordExceptionInterface $e) {
            $response = new JsonResponse(['message' => $e->getReason()], Response::HTTP_UNAUTHORIZED);

            $this->eventDispatcher->dispatch(
                new AuthEvent([
                    'response' => $response
                ]),
                AuthEvent::RESET_PASSWORD_CHECK_TOKEN_FAILURE
            );

            return $response;
        }

        $response = new JsonResponse(['message' => 'Valid token!'], Response::HTTP_OK);

        $this->eventDispatcher->dispatch(
            new AuthEvent([
                'response' => $response
            ]),
            AuthEvent::RESET_PASSWORD_CHECK_TOKEN_SUCCESS
        );

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
