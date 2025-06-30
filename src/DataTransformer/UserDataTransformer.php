<?php

namespace App\DataTransformer;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserDataTransformer implements ProcessorInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $user = $data;

        // Hashage automatique du mot de passe
        if ($user->getPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);
        }

        // Définir les rôles par défaut si pas définis
        if (empty($user->getRoles())) {
            $user->setRoles(['ROLE_USER']);
        }

        return $user;
    }
}
