<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiWriteEventSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;
 
    public function __construct(LoggerInterface $dbLogger)
    {
        $this->logger = $dbLogger;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['log', EventPriorities::POST_WRITE],
        ];
    }

    public function log(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $method = $request->getMethod();

        if (Request::METHOD_GET === $method) {
            return;
        }

        $class = $request->getPathInfo();

        $this->logger->info($method .': ' . $class, []);
    }
}