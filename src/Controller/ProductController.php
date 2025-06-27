<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractController
{
    #[Route('/api/products/create', name: 'api_products_create', methods: ['POST'])]
    public function createProduct(
        Request $request,
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
            if (!isset($data['name']) || !isset($data['price']) || !isset($data['stockQuantity'])) {
                return $this->json([
                    'error' => 'Nom, prix et quantité en stock sont requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Créer un nouveau produit
            $product = new Product();
            $product->setName(trim($data['name']));
            $product->setPrice((float) $data['price']);
            $product->setStockQuantity((int) $data['stockQuantity']);

            // Champs optionnels
            if (isset($data['description'])) {
                $product->setDescription($data['description']);
            }
            if (isset($data['imageName'])) {
                $product->setImageName($data['imageName']);
            }

            // Validation de l'entité
            $errors = $validator->validate($product);
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

            // Sauvegarder le produit en base de données
            $entityManager->persist($product);
            $entityManager->flush();

            // Sérialiser la réponse sinon sa marche pas 
            $json = $serializer->serialize($product, 'json', ['groups' => 'product:read']);

            return new Response($json, Response::HTTP_CREATED, [
                'Content-Type' => 'application/json'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la création du produit'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/products/{id}/update', name: 'api_products_update', methods: ['PUT'])]
    public function updateProduct(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): Response {
        try {
            // Trouver le produit dans la base de donnée 
            $product = $entityManager->getRepository(Product::class)->find($id);
            if (!$product) {
                return $this->json([
                    'error' => 'Produit non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Récupérer les données JSON
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

            // Mettre à jour les champs
            if (isset($data['name'])) {
                $product->setName(trim($data['name']));
            }
            if (isset($data['description'])) {
                $product->setDescription($data['description']);
            }
            if (isset($data['price'])) {
                $product->setPrice((float) $data['price']);
            }
            if (isset($data['stockQuantity'])) {
                $product->setStockQuantity((int) $data['stockQuantity']);
            }
            if (isset($data['imageName'])) {
                $product->setImageName($data['imageName']);
            }

            // Validation de l'entité
            $errors = $validator->validate($product);
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

            // Sauvegarder les modifications
            $entityManager->flush();

            // Sérialiser la réponse
            $json = $serializer->serialize($product, 'json', ['groups' => 'product:read']);

            return new Response($json, Response::HTTP_OK, [
                'Content-Type' => 'application/json'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la modification du produit'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/products/{id}/delete', name: 'api_products_delete', methods: ['DELETE'])]
    public function deleteProduct(
        int $id,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            // Trouver le produit
            $product = $entityManager->getRepository(Product::class)->find($id);
            if (!$product) {
                return $this->json([
                    'error' => 'Produit non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Supprimer le produit
            $entityManager->remove($product);
            $entityManager->flush();

            return $this->json([
                'message' => 'Produit supprimé avec succès',
                'id' => $id
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la suppression du produit'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    #[Route('/api/products/stats', name: 'api_products_stats', methods: ['GET'])]
    public function stats(EntityManagerInterface $entityManager): Response
    {
        $productRepository = $entityManager->getRepository(Product::class);

        $totalProducts = $productRepository->count([]);
        $inStockProducts = count($productRepository->findInStock());
        $recentProducts = count($productRepository->findRecentlyCreated(5));

        // Calculer le prix moyen
        $avgPrice = $entityManager->createQuery('SELECT AVG(p.price) FROM App\Entity\Product p')->getSingleScalarResult();

        return $this->json([
            'total_products' => $totalProducts,
            'in_stock_products' => $inStockProducts,
            'recent_products' => $recentProducts,
            'average_price' => round($avgPrice, 2),
            'message' => 'Statistiques des produits'
        ]);
    }
}
