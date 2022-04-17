<?php

namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserDataPersister implements DataPersisterInterface
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function supports($data): bool
    {
        return $data instanceof User;
    }

    public function persist($data, array $context = []): void
    {
        $conditionUserPost = $data instanceof User && (($context['collection_operation_name'] ?? null) === 'post');
        $conditionUserPatch = $data instanceof User && (($context['item_operation_name'] ?? null) === 'patch');
        $conditionUserPut = $data instanceof User && (($context['item_operation_name'] ?? null) === 'put');

        if ($conditionUserPost) {
            $this->hashPassword($data);
        }

        if ($conditionUserPatch || $conditionUserPut) {
            $userPassword = $context['previous_data']->getPassword();
            $passwordRecived = $data->getPassword();
            if( $userPassword != $passwordRecived ){
               $this->hashPassword($data); 
            }
        }

        $this->entityManager->persist($data, $context);
        $this->entityManager->flush();    
    }
    
    public function remove($data, array $context = [])
    {
        $conditionUserDelete = $data instanceof User && (($context['item_operation_name'] ?? null) === 'delete');

        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }

    public function hashPassword(User $data) {
        $hashedPassword = $this->passwordHasher->hashPassword(
            $data,
            $data->getPassword()
        );
        $data->setPassword($hashedPassword);
    }
}