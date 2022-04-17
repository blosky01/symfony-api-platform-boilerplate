<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Event\AuthEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuthEventSubscriber implements EventSubscriberInterface
{

    const UNKNOWN_ACTION = 'unknown_action';

    private LoggerInterface $logger;
 
    public function __construct(LoggerInterface $authLogger)
    {
        $this->logger = $authLogger;
    }

    private function log(string $action = self::UNKNOWN_ACTION, array $entityFields)
    {
        $this->logger->info($action, $entityFields);
    }

    private function getUserData(?User $data): array
    {
        if($data) return [
            'id' => $data->getId(),
            'userIdentifier' => $data->getUserIdentifier()
        ];

        return [];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            # AUTHENTICATION #
            AuthEvent::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
            AuthEvent::AUTHENTICATION_FAILURE  => 'onAuthenticationFailure',
            # REFRESH_TOKEN #
            AuthEvent::REFRESH_TOKEN_SUCCESS  => 'onRefreshTokenSuccess',
            AuthEvent::REFRESH_TOKEN_FAILURE  => 'onRefreshTokenFailure',
            # LOGOUT #
            AuthEvent::LOGOUT_SUCCESS  => 'onLogoutSuccess',
            AuthEvent::LOGOUT_FAILURE  => 'onLogoutFailure',
            # REGISTERED #
            AuthEvent::REGISTERED_SUCCESS  => 'onRegisteredSuccess',
            AuthEvent::REGISTERED_FAILURE  => 'onRegisteredFailure',
            # EMAIL_VERIFY #
            AuthEvent::EMAIL_VERIFY_EMAIL_SENDED_SUCCESS  => 'onEmailVerifyEmailSendedSuccess',
            AuthEvent::EMAIL_VERIFY_EMAIL_SENDED_FAILURE  => 'onEmailVerifyEmailSendedFailure',
            AuthEvent::EMAIL_VERIFY_SUCCESS  => 'onEmailVerifySuccess',
            AuthEvent::EMAIL_VERIFY_FAILURE  => 'onEmailVerifyFailure',
            # RESET_PASSWORD #
            AuthEvent::RESET_PASSWORD_EMAIL_SENDED_SUCCESS  => 'onResetPasswordEmailSendedSuccess',
            AuthEvent::RESET_PASSWORD_EMAIL_SENDED_FAILURE  => 'onResetPasswordEmailSendedFailure',
            AuthEvent::RESET_PASSWORD_CHECK_TOKEN_SUCCESS => 'onResetPasswordCheckTokenSuccess',
            AuthEvent::RESET_PASSWORD_CHECK_TOKEN_FAILURE => 'onResetPasswordCheckTokenFailure',
            AuthEvent::RESET_PASSWORD_SUCCESS  => 'onResetPasswordSuccess',
            AuthEvent::RESET_PASSWORD_FAILURE  => 'onResetPasswordFailure',
        ];
    }

    #> AUTHENTICATION #
    public function onAuthenticationSuccess(AuthEvent $event)
    {
        $this->log(AuthEvent::AUTHENTICATION_SUCCESS, [
            'response' => json_decode($event->getData()->getResponse()->getContent(),true),
            'user' => $this->getUserData($event->getData()->getUser())
        ]);
    }

    public function onAuthenticationFailure(AuthEvent $event)
    {
        $this->log(AuthEvent::AUTHENTICATION_FAILURE, [
            'response' => json_decode($event->getData()->getResponse()->getContent(),true)
        ]);
    }
    #< AUTHENTICATION #

    #> REFRESH_TOKEN #
    public function onRefreshTokenSuccess(AuthEvent $event)
    {
        $this->log(AuthEvent::REFRESH_TOKEN_SUCCESS, [
            'response' => [],
            'user' => $this->getUserData($event->getData()->getToken()->getUser())
        ]);
    }

    public function onRefreshTokenFailure(AuthEvent $event)
    {
        $this->log(AuthEvent::REFRESH_TOKEN_FAILURE, [
            'response' => json_decode($event->getData()->getResponse()->getContent(),true)
        ]);
    }
    #< REFRESH_TOKEN #

    #> LOGOUT #
    public function onLogoutSuccess(AuthEvent $event)
    {
        $this->log(AuthEvent::LOGOUT_SUCCESS, [
            'response' => json_decode($event->getData()->getResponse()->getContent(),true),
            'user' => $this->getUserData($event->getData()->getToken()->getUser())
        ]);
    }

    public function onLogoutFailure(AuthEvent $event)
    {
        $this->log(AuthEvent::LOGOUT_FAILURE, [
            'event' => $event->getData()
        ]);
    }
    #< LOGOUT #

    #> REGISTERED #
    public function onRegisteredSuccess(AuthEvent $event)
    {
        $this->log(AuthEvent::REGISTERED_SUCCESS, [
            'user' => $this->getUserData($event->getData())
        ]);
    }

    public function onRegisteredFailure(AuthEvent $event)
    {
        $this->log(AuthEvent::REGISTERED_FAILURE, [
            'user' => $this->getUserData($event->getData())
        ]);
    }
    #< REGISTERED #

    #> EMAIL_VERIFY #
    public function onEmailVerifyEmailSendedSuccess(AuthEvent $event)
    {
        $this->log(AuthEvent::EMAIL_VERIFY_EMAIL_SENDED_SUCCESS, [
            'response' => [],
            'user' => $this->getUserData($event->getData())
        ]);
    }

    public function onEmailVerifyEmailSendedFailure(AuthEvent $event)
    {
        $this->log(AuthEvent::EMAIL_VERIFY_EMAIL_SENDED_FAILURE, [
            'response' => [],
            'user' => $this->getUserData($event->getData())
        ]);
    }

    public function onEmailVerifySuccess(AuthEvent $event)
    {
        $this->log(AuthEvent::EMAIL_VERIFY_SUCCESS, [
            'response' => json_decode($event->getData()['response']->getContent(),true),
            'user' => $this->getUserData($event->getData()['user'])
        ]);
    }

    public function onEmailVerifyFailure(AuthEvent $event)
    {
        $this->log(AuthEvent::EMAIL_VERIFY_FAILURE, [
            'response' => json_decode($event->getData()['response']->getContent(),true),
            'user' => $this->getUserData($event->getData()['user'])
        ]);
    }
    #< EMAIL_VERIFY #

    #> RESET_PASSWORD #
    public function onResetPasswordEmailSendedSuccess(AuthEvent $event)
    {
        $this->log(AuthEvent::RESET_PASSWORD_EMAIL_SENDED_SUCCESS, [
            'response' => json_decode($event->getData()['response']->getContent(),true),
            'email' => $event->getData()['email']
        ]);
    }

    public function onResetPasswordEmailSendedFailure(AuthEvent $event)
    {
        $this->log(AuthEvent::RESET_PASSWORD_EMAIL_SENDED_FAILURE, [
            'response' => json_decode($event->getData()['response']->getContent(),true),
            'email' => $event->getData()['email']
        ]);
    }

    public function onResetPasswordCheckTokenSuccess(AuthEvent $event)
    {
        $this->log(AuthEvent::RESET_PASSWORD_CHECK_TOKEN_SUCCESS, [
            'response' => json_decode($event->getData()['response']->getContent(),true),
        ]);
    }

    public function onResetPasswordCheckTokenFailure(AuthEvent $event)
    {
        $this->log(AuthEvent::RESET_PASSWORD_CHECK_TOKEN_FAILURE, [
            'response' => json_decode($event->getData()['response']->getContent(),true),
        ]);
    }

    public function onResetPasswordSuccess(AuthEvent $event)
    {
        $this->log(AuthEvent::RESET_PASSWORD_SUCCESS, [
            'response' => json_decode($event->getData()['response']->getContent(),true),
            'user' => $this->getUserData($event->getData()['user'])
        ]);
    }

    public function onResetPasswordFailure(AuthEvent $event)
    {
        $this->log(AuthEvent::RESET_PASSWORD_FAILURE, [
            'response' => json_decode($event->getData()['response']->getContent(),true)
        ]);
    }
    #< RESET_PASSWORD #
}