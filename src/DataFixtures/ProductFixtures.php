<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $products = [
            [
                'name' => 'iPhone 15 Pro',
                'description' => 'Le dernier iPhone avec puce A17 Pro, appareil photo professionnel et design en titane.',
                'price' => 1199.99,
                'stockQuantity' => 25,
                'imageName' => 'iphone15pro.jpg'
            ],
            [
                'name' => 'MacBook Air M2',
                'description' => 'Ordinateur portable ultra-léger avec puce M2, parfait pour la productivité.',
                'price' => 1299.99,
                'stockQuantity' => 15,
                'imageName' => 'macbook-air-m2.jpg'
            ],
            [
                'name' => 'iPad Air',
                'description' => 'Tablette polyvalente avec puce M1, idéale pour le travail et les loisirs.',
                'price' => 699.99,
                'stockQuantity' => 30,
                'imageName' => 'ipad-air.jpg'
            ],
            [
                'name' => 'AirPods Pro',
                'description' => 'Écouteurs sans fil avec réduction de bruit active et audio spatial.',
                'price' => 249.99,
                'stockQuantity' => 50,
                'imageName' => 'airpods-pro.jpg'
            ],
            [
                'name' => 'Apple Watch Series 9',
                'description' => 'Montre connectée avec suivi santé avancé et design élégant.',
                'price' => 399.99,
                'stockQuantity' => 20,
                'imageName' => 'apple-watch-series9.jpg'
            ],
            [
                'name' => 'iMac 24"',
                'description' => 'Ordinateur tout-en-un avec écran Retina 4.5K et puce M1.',
                'price' => 1499.99,
                'stockQuantity' => 10,
                'imageName' => 'imac-24.jpg'
            ],
            [
                'name' => 'Magic Keyboard',
                'description' => 'Clavier sans fil avec design minimaliste et touches rétroéclairées.',
                'price' => 99.99,
                'stockQuantity' => 40,
                'imageName' => 'magic-keyboard.jpg'
            ],
            [
                'name' => 'Magic Mouse',
                'description' => 'Souris sans fil avec surface tactile et design ergonomique.',
                'price' => 79.99,
                'stockQuantity' => 35,
                'imageName' => 'magic-mouse.jpg'
            ]
        ];

        foreach ($products as $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setStockQuantity($productData['stockQuantity']);
            $product->setImageName($productData['imageName']);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
