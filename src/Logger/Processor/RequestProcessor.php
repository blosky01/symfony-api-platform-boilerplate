<?php

namespace App\Logger\Processor;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @method User|null getUser()
 */
class RequestProcessor extends AbstractController
{

    protected RequestStack $requestStack;

    public function __construct(
        RequestStack $requestStack
    ) {
        $this->requestStack = $requestStack;
    }

    public function processRecord(array $record): array
    {
        $requestStack = $this->requestStack->getCurrentRequest();
        $record['extra']['client_ip'] = $requestStack->getClientIp();
        $record['extra']['client_port'] = $requestStack->getPort();
        $record['extra']['uri'] = $requestStack->getUri();
        $record['extra']['query_string'] = $requestStack->getQueryString();
        $record['extra']['method'] = $requestStack->getMethod();
        $record['extra']['request'] = $requestStack->request->all();
        $record['extra']['body'] = $requestStack->getContent();
        $record['user'] = $this->getUser() ? '/api/users/' . $this->getUser()->getId() : 'anonymous';

        return $record;
    }
}