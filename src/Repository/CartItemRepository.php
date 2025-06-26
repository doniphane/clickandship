<?php

namespace App\Repository;

use App\Entity\CartItem;
use App\Entity\User;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartItem>
 *
 * @method CartItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method CartItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method CartItem[]    findAll()
 * @method CartItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    public function save(CartItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CartItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve tous les articles du panier d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('ci')
            ->andWhere('ci.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ci.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un article spécifique dans le panier d'un utilisateur
     */
    public function findByUserAndProduct(User $user, Product $product): ?CartItem
    {
        return $this->createQueryBuilder('ci')
            ->andWhere('ci.user = :user')
            ->andWhere('ci.product = :product')
            ->setParameter('user', $user)
            ->setParameter('product', $product)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte le nombre d'articles dans le panier d'un utilisateur
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('ci')
            ->select('COUNT(ci.id)')
            ->andWhere('ci.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule le total du panier d'un utilisateur
     */
    public function getTotalByUser(User $user): float
    {
        $result = $this->createQueryBuilder('ci')
            ->select('SUM(ci.quantity * p.price)')
            ->join('ci.product', 'p')
            ->andWhere('ci.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0.0;
    }

    /**
     * Vide le panier d'un utilisateur
     */
    public function clearByUser(User $user): int
    {
        return $this->createQueryBuilder('ci')
            ->delete()
            ->andWhere('ci.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
