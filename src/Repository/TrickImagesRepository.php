<?php

namespace App\Repository;

use App\Entity\TrickImages;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TrickImages|null find($id, $lockMode = null, $lockVersion = null)
 * @method TrickImages|null findOneBy(array $criteria, array $orderBy = null)
 * @method TrickImages[]    findAll()
 * @method TrickImages[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrickImagesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrickImages::class);
    }

    // /**
    //  * @return TrickImages[] Returns an array of TrickImages objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TrickImages
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
