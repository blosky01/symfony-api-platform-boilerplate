<?php

namespace App\Logger;

use App\Entity\ApiWriteLog;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\AbstractProcessingHandler;

class MonologApiWriteHandler extends AbstractProcessingHandler
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function write(array $record): void
    {
        $log = new ApiWriteLog();
        $log->setMessage($record['message']);
        $log->setLevel($record['level']);
        $log->setLevelName($record['level_name']);
        $log->setExtra($record['extra']);
        $log->setContext($record['context']);
        $log->setRecordBy($record['user']);
        
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}