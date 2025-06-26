<?php

namespace App\Repository;

use App\Entity\OrderItem;
use App\Entity\Order;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderItem>
 *
 * @method OrderItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderItem[]    findAll()
 * @method OrderItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    public function save(OrderItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OrderItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve tous les articles d'une commande
     */
    public function findByOrder(Order $order): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.order = :order')
            ->setParameter('order', $order)
            ->orderBy('oi.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les articles par produit
     */
    public function findByProduct(Product $product): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.product = :product')
            ->setParameter('product', $product)
            ->orderBy('oi.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d'articles d'une commande
     */
    public function countByOrder(Order $order): int
    {
        return $this->createQueryBuilder('oi')
            ->select('COUNT(oi.id)')
            ->andWhere('oi.order = :order')
            ->setParameter('order', $order)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule le total d'une commande basé sur ses articles
     */
    public function getTotalByOrder(Order $order): float
    {
        $result = $this->createQueryBuilder('oi')
            ->select('SUM(oi.unitPrice * oi.quantity)')
            ->andWhere('oi.order = :order')
            ->setParameter('order', $order)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0.0;
    }

    /**
     * Trouve les articles les plus commandés
     */
    public function findMostOrderedProducts(int $limit = 10): array
    {
        return $this->createQueryBuilder('oi')
            ->select('p.id, p.name, SUM(oi.quantity) as totalQuantity')
            ->join('oi.product', 'p')
            ->groupBy('p.id, p.name')
            ->orderBy('totalQuantity', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
