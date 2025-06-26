<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    #[Route('/api/users', name: 'api_users_create', methods: ['POST'])]
    public function createUser(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): Response {
        try {
            // Récupérer et valider les données JSON
            $content = $request->getContent();
            if (empty($content)) {
                return $this->json([
                    'error' => 'Le contenu de la requête ne peut pas être vide'
                ], Response::HTTP_BAD_REQUEST);
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'error' => 'Format JSON invalide'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation des champs requis
            if (!isset($data['email']) || !isset($data['password'])) {
                return $this->json([
                    'error' => 'Email et mot de passe sont requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $email = trim($data['email']);
            $password = $data['password'];

            // Validation de l'email
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->json([
                    'error' => 'Format d\'email invalide'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation du mot de passe
            if (empty($password) || strlen($password) < 6) {
                return $this->json([
                    'error' => 'Le mot de passe doit contenir au moins 6 caractères'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier si l'utilisateur existe déjà
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                return $this->json([
                    'error' => 'Un utilisateur avec cet email existe déjà'
                ], Response::HTTP_CONFLICT);
            }

            // Créer un nouvel utilisateur
            $user = new User();
            $user->setEmail($email);

            // Hashage sécurisé du mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Définir les rôles par défaut
            $user->setRoles(['ROLE_USER']);

            // Validation de l'entité
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json([
                    'error' => 'Données invalides',
                    'details' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            // Sauvegarder l'utilisateur en base de données
            $entityManager->persist($user);
            $entityManager->flush();

            // Sérialiser la réponse (sans le mot de passe)
            $json = $serializer->serialize($user, 'json', ['groups' => 'user:read']);

            return new Response($json, Response::HTTP_CREATED, [
                'Content-Type' => 'application/json'
            ]);
        } catch (\Exception $e) {
            // Log de l'erreur (en production, utilisez un logger)
            return $this->json([
                'error' => 'Une erreur est survenue lors de la création de l\'utilisateur'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
