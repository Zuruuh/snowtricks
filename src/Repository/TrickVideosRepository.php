<?php

namespace App\Repository;

use App\Entity\TrickVideos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TrickVideos|null find($id, $lockMode = null, $lockVersion = null)
 * @method TrickVideos|null findOneBy(array $criteria, array $orderBy = null)
 * @method TrickVideos[]    findAll()
 * @method TrickVideos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrickVideosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrickVideos::class);
    }

    // /**
    //  * @return TrickVideos[] Returns an array of TrickVideos objects
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
    public function findOneBySomeField($value): ?TrickVideos
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
