<?php

namespace App\Logger;

use App\Entity\AuthLog;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\AbstractProcessingHandler;

class MonologAuthLogHandler extends AbstractProcessingHandler
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function write(array $record): void
    {
        $authLog = new AuthLog();
        $authLog->setMessage($record['message']);
        $authLog->setLevel($record['level']);
        $authLog->setLevelName($record['level_name']);
        $authLog->setExtra($record['extra']);
        $authLog->setContext($record['context']);
        $authLog->setRecordBy($record['user']);
        
        $this->entityManager->persist($authLog);
        $this->entityManager->flush();
    }
}