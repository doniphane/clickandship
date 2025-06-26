<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

class OrderController extends AbstractController
{
    #[Route('/api/orders', name: 'api_orders_get', methods: ['GET'])]
    public function getOrders(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        try {
            // Vérifier que l'utilisateur est connecté
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException('Vous devez être connecté pour accéder à vos commandes');
            }

            // Récupérer toutes les commandes de l'utilisateur
            $orders = $entityManager->getRepository(Order::class)->findByUser($user);

            // Calculer les statistiques
            $totalOrders = count($orders);
            $totalSpent = $entityManager->getRepository(Order::class)->getTotalByUser($user);

            // Sérialiser la réponse
            $json = $serializer->serialize($orders, 'json', ['groups' => 'order:read']);

            return new JsonResponse([
                'orders' => json_decode($json, true),
                'total_orders' => $totalOrders,
                'total_spent' => round($totalSpent, 2),
                'message' => 'Commandes récupérées avec succès'
            ], Response::HTTP_OK);
        } catch (AccessDeniedException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la récupération des commandes'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/orders/{id}', name: 'api_order_get', methods: ['GET'])]
    public function getOrder(
        int $id,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        try {
            // Vérifier que l'utilisateur est connecté
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException('Vous devez être connecté pour accéder à vos commandes');
            }

            // Récupérer la commande spécifique de l'utilisateur
            $order = $entityManager->getRepository(Order::class)->findByUserAndId($user, $id);

            if (!$order) {
                return $this->json([
                    'error' => 'Commande non trouvée ou vous n\'avez pas accès à cette commande'
                ], Response::HTTP_NOT_FOUND);
            }

            // Récupérer les articles de la commandes 
            $orderItems = $entityManager->getRepository(\App\Entity\OrderItem::class)->findByOrder($order);

            // Calculer le total vérifié
            $calculatedTotal = $entityManager->getRepository(\App\Entity\OrderItem::class)->getTotalByOrder($order);

            // Sérialiser la réponse sison i marche pas 
            $orderJson = $serializer->serialize($order, 'json', ['groups' => 'order:read']);
            $orderItemsJson = $serializer->serialize($orderItems, 'json', ['groups' => 'order_item:read']);

            return new JsonResponse([
                'order' => json_decode($orderJson, true),
                'order_items' => json_decode($orderItemsJson, true),
                'calculated_total' => round($calculatedTotal, 2),
                'items_count' => count($orderItems),
                'message' => 'Commande récupérée avec succès'
            ], Response::HTTP_OK);
        } catch (AccessDeniedException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la récupération de la commande'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/orders/status/{status}', name: 'api_orders_by_status', methods: ['GET'])]
    public function getOrdersByStatus(
        string $status,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        try {
            // Vérifier que l'utilisateur est connecté
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException('Vous devez être connecté pour accéder à vos commandes');
            }

            // Valider le statut
            $validStatuses = ['en_attente', 'payé', 'expédié', 'livré', 'annulé'];
            if (!in_array($status, $validStatuses)) {
                return $this->json([
                    'error' => 'Statut invalide. Statuts valides: ' . implode(', ', $validStatuses)
                ], Response::HTTP_BAD_REQUEST);
            }

            // Récupérer les commandes par statut
            $orders = $entityManager->getRepository(Order::class)->findByUserAndStatus($user, $status);

            // Sérialiser la réponse
            $json = $serializer->serialize($orders, 'json', ['groups' => 'order:read']);

            return new JsonResponse([
                'orders' => json_decode($json, true),
                'status' => $status,
                'count' => count($orders),
                'message' => "Commandes avec le statut '$status' récupérées avec succès"
            ], Response::HTTP_OK);
        } catch (AccessDeniedException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la récupération des commandes'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/orders/recent', name: 'api_orders_recent', methods: ['GET'])]
    public function getRecentOrders(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        try {
            // Vérifier que l'utilisateur est connecté
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException('Vous devez être connecté pour accéder à vos commandes');
            }

            // Récupérer les commandes récentes (limite par défaut: 5)
            $orders = $entityManager->getRepository(Order::class)->findRecentByUser($user, 5);

            // Sérialiser la réponse
            $json = $serializer->serialize($orders, 'json', ['groups' => 'order:read']);

            return new JsonResponse([
                'orders' => json_decode($json, true),
                'count' => count($orders),
                'message' => 'Commandes récentes récupérées avec succès'
            ], Response::HTTP_OK);
        } catch (AccessDeniedException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la récupération des commandes récentes'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
