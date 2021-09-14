<?php

namespace App\Repository;

use App\Entity\Trick;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Trick|null find($id, $lockMode = null, $lockVersion = null)
 * @method Trick|null findOneBy(array $criteria, array $orderBy = null)
 * @method Trick[]    findAll()
 * @method Trick[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trick::class);
    }

    public function search(string $keywords = "", int $category = 0, int $offset = 0, int $limit = 10): array
    {
        // TODO Implement pagination
        $query = $this->createQueryBuilder('t');
        $query->where("t.id > 1");
        
        if ($keywords !== "") {
            $query->andWhere('MATCH (t.name, t.description, t.overview) AGAINST (:keywords boolean) > 0')
                ->setParameter('keywords', $keywords);
        }
        
        if ($category > 0) {
            $query->leftJoin("t.category", "c")
                ->andWhere("c.id = :category")
                ->setParameter('category', $category);
        }

        $query->orderBy('t.id', 'DESC');

        $results = $query->getQuery()->getResult();

        return $results;
    }

    // /**
    //  * @return Trick[] Returns an array of Trick objects
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
    public function findOneBySomeField($value): ?Trick
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
