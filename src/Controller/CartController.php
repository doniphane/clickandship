<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CartController extends AbstractController
{
    #[Route('/api/cart', name: 'api_cart_get', methods: ['GET'])]
    public function getCart(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        try {
            // Vérifier que l'utilisateur est connecté
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException('Vous devez être connecté pour accéder au panier');
            }

            // Récupérer les articles du panier
            $cartItems = $entityManager->getRepository(CartItem::class)->findByUser($user);

            // Calculer le total
            $total = $entityManager->getRepository(CartItem::class)->getTotalByUser($user);
            $itemCount = count($cartItems);

            // Sérialiser la réponse
            $json = $serializer->serialize($cartItems, 'json', ['groups' => 'cart:read']);

            return new JsonResponse([
                'cart_items' => json_decode($json, true),
                'total_items' => $itemCount,
                'total_price' => round($total, 2),
                'message' => 'Panier récupéré avec succès'
            ], Response::HTTP_OK);
        } catch (AccessDeniedException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la récupération du panier'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/cart/add', name: 'api_cart_add', methods: ['POST'])]
    public function addToCart(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            // Vérifier que l'utilisateur est connecté
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException('Vous devez être connecté pour ajouter au panier');
            }

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
            if (!isset($data['productId']) || !isset($data['quantity'])) {
                return $this->json([
                    'error' => 'productId et quantity sont requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $productId = (int) $data['productId'];
            $quantity = (int) $data['quantity'];

            // Validation de la quantité
            if ($quantity <= 0) {
                return $this->json([
                    'error' => 'La quantité doit être positive'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier que le produit existe
            $product = $entityManager->getRepository(Product::class)->find($productId);
            if (!$product) {
                return $this->json([
                    'error' => 'Produit non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérifier le stock disponible
            if ($product->getStockQuantity() < $quantity) {
                return $this->json([
                    'error' => 'Stock insuffisant. Disponible: ' . $product->getStockQuantity()
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier si le produit est déjà dans le panier
            $existingCartItem = $entityManager->getRepository(CartItem::class)->findByUserAndProduct($user, $product);

            if ($existingCartItem) {
                // Incrémenter la quantité
                $newQuantity = $existingCartItem->getQuantity() + $quantity;

                // Vérifier le stock total
                if ($product->getStockQuantity() < $newQuantity) {
                    return $this->json([
                        'error' => 'Stock insuffisant pour cette quantité totale. Disponible: ' . $product->getStockQuantity()
                    ], Response::HTTP_BAD_REQUEST);
                }

                $existingCartItem->setQuantity($newQuantity);
                $cartItem = $existingCartItem;
            } else {
                // Créer un nouvel article
                $cartItem = new CartItem();
                $cartItem->setUser($user);
                $cartItem->setProduct($product);
                $cartItem->setQuantity($quantity);

                $entityManager->persist($cartItem);
            }

            // Validation de l'entité
            $errors = $validator->validate($cartItem);
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

            // Sauvegarder en base de données
            $entityManager->flush();

            // Récupérer le panier mis à jour
            $updatedCartItems = $entityManager->getRepository(CartItem::class)->findByUser($user);
            $total = $entityManager->getRepository(CartItem::class)->getTotalByUser($user);

            // Sérialiser la réponse
            $json = $serializer->serialize($updatedCartItems, 'json', ['groups' => 'cart:read']);

            return new JsonResponse([
                'cart_items' => json_decode($json, true),
                'total_items' => count($updatedCartItems),
                'total_price' => round($total, 2),
                'message' => 'Produit ajouté au panier avec succès',
                'added_product' => [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'quantity' => $quantity
                ]
            ], Response::HTTP_OK);
        } catch (AccessDeniedException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de l\'ajout au panier'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/cart/remove', name: 'api_cart_remove', methods: ['POST'])]
    public function removeFromCart(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        try {
            // Vérifier que l'utilisateur est connecté
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException('Vous devez être connecté pour modifier le panier');
            }

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
            if (!isset($data['productId'])) {
                return $this->json([
                    'error' => 'productId est requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $productId = (int) $data['productId'];

            // Vérifier que le produit existe
            $product = $entityManager->getRepository(Product::class)->find($productId);
            if (!$product) {
                return $this->json([
                    'error' => 'Produit non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérifier si le produit est dans le panier
            $cartItem = $entityManager->getRepository(CartItem::class)->findByUserAndProduct($user, $product);
            if (!$cartItem) {
                return $this->json([
                    'error' => 'Ce produit n\'est pas dans votre panier'
                ], Response::HTTP_NOT_FOUND);
            }

            // Supprimer l'article du panier
            $entityManager->remove($cartItem);
            $entityManager->flush();

            // Récupérer le panier mis à jour
            $updatedCartItems = $entityManager->getRepository(CartItem::class)->findByUser($user);
            $total = $entityManager->getRepository(CartItem::class)->getTotalByUser($user);

            // Sérialiser la réponse
            $json = $serializer->serialize($updatedCartItems, 'json', ['groups' => 'cart:read']);

            return new JsonResponse([
                'cart_items' => json_decode($json, true),
                'total_items' => count($updatedCartItems),
                'total_price' => round($total, 2),
                'message' => 'Produit retiré du panier avec succès',
                'removed_product' => [
                    'id' => $product->getId(),
                    'name' => $product->getName()
                ]
            ], Response::HTTP_OK);
        } catch (AccessDeniedException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la suppression du panier'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/cart/clear', name: 'api_cart_clear', methods: ['POST'])]
    public function clearCart(
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            // Vérifier que l'utilisateur est connecté
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException('Vous devez être connecté pour vider le panier');
            }

            // Vider le panier
            $removedCount = $entityManager->getRepository(CartItem::class)->clearByUser($user);

            return new JsonResponse([
                'cart_items' => [],
                'total_items' => 0,
                'total_price' => 0,
                'message' => 'Panier vidé avec succès',
                'removed_items' => $removedCount
            ], Response::HTTP_OK);
        } catch (AccessDeniedException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors du vidage du panier'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
