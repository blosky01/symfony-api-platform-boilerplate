<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Event\AuthEvent;
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
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
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
        Environment $twig,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->verifyEmailHelper = $helper;
        $this->entityManager = $entityManager;
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->eventDispatcher = $eventDispatcher;
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

            $this->eventDispatcher->dispatch(
                new AuthEvent([
                    'response' => $response,
                    'user' => null
                ]),
                AuthEvent::EMAIL_VERIFY_FAILURE
            );

            return $response;
        }

        $user = null;

        if (is_string($id) && (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id) === 1)) {
            $user = $userRepository->find($id);
        }        

        if (!$user) {

            $response = new JsonResponse('User not found.', JsonResponse::HTTP_UNAUTHORIZED);

            $this->eventDispatcher->dispatch(
                new AuthEvent([
                    'response' => $response,
                    'user' => $user
                ]),
                AuthEvent::EMAIL_VERIFY_FAILURE
            );

            return $response;
        }

        if ($user->getEmailVerify()) {

            $response = new JsonResponse('User is verified yet.', JsonResponse::HTTP_UNAUTHORIZED);

            $this->eventDispatcher->dispatch(
                new AuthEvent([
                    'response' => $response,
                    'user' => $user
                ]),
                AuthEvent::EMAIL_VERIFY_FAILURE
            );

            return $response;
        }

        try {
            $uri = $request->getUri();
            $userID = $user->getId();
            $userEmail = $user->getEmail();
            $this->verifyEmailHelper->validateEmailConfirmation($uri, $userID, $userEmail);
        } catch (VerifyEmailExceptionInterface $e) {

            $response = new JsonResponse($e->getReason(), JsonResponse::HTTP_UNAUTHORIZED);

            $this->eventDispatcher->dispatch(
                new AuthEvent([
                    'response' => $response,
                    'user' => $user
                ]),
                AuthEvent::EMAIL_VERIFY_FAILURE
            );

            return $response;
        }

        $user->setEmailVerify(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $response = new JsonResponse(['message' => 'Your e-mail address has been verified.'], Response::HTTP_OK);

        $this->eventDispatcher->dispatch(
            new AuthEvent([
                'response' => $response,
                'user' => $user
            ]),
            AuthEvent::EMAIL_VERIFY_SUCCESS
        );

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

            $this->eventDispatcher->dispatch(
                new AuthEvent($data),
                AuthEvent::EMAIL_VERIFY_EMAIL_SENDED_FAILURE
            );

            throw new NotFoundHttpException('Unable to send email');
        }

        $this->eventDispatcher->dispatch(
            new AuthEvent($data),
            AuthEvent::EMAIL_VERIFY_EMAIL_SENDED_SUCCESS
        );
    }
}
