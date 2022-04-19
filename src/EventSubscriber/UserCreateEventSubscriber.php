<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Controller\Auth\RegistrationConfirmation;
use App\Entity\User;
use App\Event\AuthEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class UserCreateEventSubscriber implements EventSubscriberInterface
{
    private RegistrationConfirmation $registerVerify;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        RegistrationConfirmation $registerVerify,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->registerVerify = $registerVerify;
        $this->eventDispatcher = $eventDispatcher;
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

        if (!$user instanceof User || Request::METHOD_POST === $method) {
            return;
        }

        $this->registerVerify->sendVerificationEmail($user);
        $this->eventDispatcher->dispatch(
            new AuthEvent($user),
            AuthEvent::REGISTERED_SUCCESS
        );
    }
}
