<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'api_product_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            // Récupérer les données du formulaire multipart
            $name = $request->request->get('name');
            $description = $request->request->get('description');
            $price = $request->request->get('price');
            $stockQuantity = $request->request->get('stockQuantity');
            $imageFile = $request->files->get('imageFile');

            // Validation des données obligatoires
            if (!$name || !$price || !$stockQuantity) {
                return $this->json([
                    'error' => 'Les champs name, price et stockQuantity sont obligatoires'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Créer le produit
            $product = new Product();
            $product->setName($name);
            $product->setDescription($description);
            $product->setPrice((float) $price);
            $product->setStockQuantity((int) $stockQuantity);

            // Gérer l'upload d'image si fourni
            if ($imageFile) {
                $product->setImageFile($imageFile);
            }

            // Valider l'entité
            $errors = $this->validator->validate($product);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json([
                    'error' => 'Erreurs de validation',
                    'details' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            // Persister en base
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            // Sérialiser la réponse
            $productJson = $this->serializer->serialize($product, 'json', ['groups' => 'product:read']);

            return $this->json([
                'message' => 'Produit créé avec succès',
                'product' => json_decode($productJson, true)
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la création du produit',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_product_update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Récupérer le produit
            $product = $this->entityManager->getRepository(Product::class)->find($id);
            if (!$product) {
                return $this->json([
                    'error' => 'Produit non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Récupérer les données du formulaire multipart
            $name = $request->request->get('name');
            $description = $request->request->get('description');
            $price = $request->request->get('price');
            $stockQuantity = $request->request->get('stockQuantity');
            $imageFile = $request->files->get('imageFile');

            // Mettre à jour les champs si fournis
            if ($name !== null) {
                $product->setName($name);
            }
            if ($description !== null) {
                $product->setDescription($description);
            }
            if ($price !== null) {
                $product->setPrice((float) $price);
            }
            if ($stockQuantity !== null) {
                $product->setStockQuantity((int) $stockQuantity);
            }

            // Gérer l'upload d'image si fourni
            if ($imageFile) {
                $product->setImageFile($imageFile);
            }

            // Valider l'entité
            $errors = $this->validator->validate($product);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json([
                    'error' => 'Erreurs de validation',
                    'details' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            // Persister en base
            $this->entityManager->flush();

            // Sérialiser la réponse
            $productJson = $this->serializer->serialize($product, 'json', ['groups' => 'product:read']);

            return $this->json([
                'message' => 'Produit mis à jour avec succès',
                'product' => json_decode($productJson, true)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la mise à jour du produit',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_product_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            // Récupérer le produit
            $product = $this->entityManager->getRepository(Product::class)->find($id);
            if (!$product) {
                return $this->json([
                    'error' => 'Produit non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Supprimer le produit (l'image sera automatiquement supprimée par VichUploader)
            $this->entityManager->remove($product);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Produit supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la suppression du produit',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/test', name: 'api_products_test', methods: ['GET'])]
    public function test(): Response
    {
        return $this->json([
            'message' => 'API des produits fonctionne correctement !',
            'endpoints' => [
                'GET /api/products' => 'Liste tous les produits (API Platform)',
                'GET /api/products/{id}' => 'Voir un produit spécifique (API Platform)',
                'POST /api/products/create' => 'Créer un produit (sans authentification)',
                'PUT /api/products/{id}/update' => 'Modifier un produit (sans authentification)',
                'DELETE /api/products/{id}/delete' => 'Supprimer un produit (sans authentification)'
            ]
        ]);
    }

    #[Route('/stats', name: 'api_products_stats', methods: ['GET'])]
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
