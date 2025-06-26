<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api/test', name: 'api_test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        $user = $this->getUser();

        return $this->json([
            'message' => 'Route protégée accessible !',
            'user' => [
                'email' => $user->getUserIdentifier(),
                'roles' => $user->getRoles()
            ]
        ]);
    }
}
