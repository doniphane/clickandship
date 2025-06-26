<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer l'utilisateur de test
        $user = $manager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);

        if (!$user) {
            return; // Pas d'utilisateur, on ne peut pas créer de commandes
        }

        // Récupérer les produits
        $products = $manager->getRepository(Product::class)->findAll();

        if (empty($products)) {
            return; // Pas de produits, on ne peut pas créer de commandes
        }

        // Créer plusieurs commandes de test
        $orders = [
            [
                'status' => 'payé',
                'items' => [
                    ['product' => $products[0], 'quantity' => 1, 'unit_price' => $products[0]->getPrice()],
                    ['product' => $products[1], 'quantity' => 2, 'unit_price' => $products[1]->getPrice()]
                ]
            ],
            [
                'status' => 'expédié',
                'items' => [
                    ['product' => $products[2], 'quantity' => 1, 'unit_price' => $products[2]->getPrice()],
                    ['product' => $products[3], 'quantity' => 1, 'unit_price' => $products[3]->getPrice()],
                    ['product' => $products[4], 'quantity' => 3, 'unit_price' => $products[4]->getPrice()]
                ]
            ],
            [
                'status' => 'en_attente',
                'items' => [
                    ['product' => $products[5], 'quantity' => 1, 'unit_price' => $products[5]->getPrice()]
                ]
            ],
            [
                'status' => 'livré',
                'items' => [
                    ['product' => $products[0], 'quantity' => 2, 'unit_price' => $products[0]->getPrice()],
                    ['product' => $products[6], 'quantity' => 1, 'unit_price' => $products[6]->getPrice()]
                ]
            ]
        ];

        foreach ($orders as $orderData) {
            // Créer la commande
            $order = new Order();
            $order->setUser($user);
            $order->setStatus($orderData['status']);

            // Calculer le total
            $total = 0;

            // Ajouter les articles
            foreach ($orderData['items'] as $itemData) {
                $orderItem = new OrderItem();
                $orderItem->setOrder($order);
                $orderItem->setProduct($itemData['product']);
                $orderItem->setQuantity($itemData['quantity']);
                $orderItem->setUnitPrice($itemData['unit_price']);

                $total += $itemData['quantity'] * $itemData['unit_price'];

                $manager->persist($orderItem);
            }

            // Définir le total de la commande
            $order->setTotal($total);

            $manager->persist($order);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ProductFixtures::class,
        ];
    }
}
