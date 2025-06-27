<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// Le contrôleur s'occupe de gérer les inscriptions des utilisateurs (authentification)
class AuthController extends AbstractController
{
    // Cette route permet d’enregistrer un nouvel utilisateur 
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request, // Permet de récupérer les données envoyées dans la requête HTTP
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        // On décode les données JSON envoyées dans le body de la requête
        $data = json_decode($request->getContent(), true);

        // On vérifie que l'email et le mot de passe ont bien été fournis
        if (!$data || !isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'error' => 'Email et mot de passe requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'];
        $password = $data['password'];

        // On vérifie si un utilisateur avec cet email existe déjà en base
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->json([
                'error' => 'Un utilisateur avec cet email existe déjà'
            ], Response::HTTP_CONFLICT);
        }

        // crée un nouvel utilisateur
        $user = new User();
        $user->setEmail($email);

        // On chiffrement du mot de passe avant de l’enregistrer 
        $user->setPassword($passwordHasher->hashPassword($user, $password));

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


        $entityManager->persist($user);
        $entityManager->flush();

        // On retourne une réponse de succès avec les infos principales de l'utilisateur
        return $this->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail()
            ]
        ], Response::HTTP_CREATED);
    }
}
