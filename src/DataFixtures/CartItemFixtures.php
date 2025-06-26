<?php

namespace App\DataFixtures;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CartItemFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer l'utilisateur de test
        $user = $manager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);

        if (!$user) {
            return; // Pas d'utilisateur, on ne peut pas créer de panier
        }

        // Récupérer quelques produits
        $products = $manager->getRepository(Product::class)->findAll();

        if (empty($products)) {
            return; // Pas de produits, on ne peut pas créer de panier
        }

        // Ajouter quelques produits au panier
        $cartItems = [
            [
                'product' => $products[0], // Premier produit
                'quantity' => 2
            ],
            [
                'product' => $products[1], // Deuxième produit
                'quantity' => 1
            ],
            [
                'product' => $products[2], // Troisième produit
                'quantity' => 3
            ]
        ];

        foreach ($cartItems as $itemData) {
            $cartItem = new CartItem();
            $cartItem->setUser($user);
            $cartItem->setProduct($itemData['product']);
            $cartItem->setQuantity($itemData['quantity']);

            $manager->persist($cartItem);
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
