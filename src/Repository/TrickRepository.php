<?php

namespace App\Repository;

use App\Entity\Trick;
use App\Entity\User;
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

    public function search(
        string $keywords = '',
        int $category = 0,
        int $offset = 0,
        int $limit = 0,
        bool $count = false
    ): array|int {
        $query = $this->createQueryBuilder('t');
        $query->where('t.id > 1');

        if ($keywords) {
            $query->andWhere('MATCH (t.name, t.description, t.overview) AGAINST (:keywords boolean) > 0')
                ->setParameter('keywords', $keywords);
        }

        if ($category > 0) {
            $query->leftJoin('t.category', 'c')
                ->andWhere('c.id = :category')
                ->setParameter('category', $category);
        }

        if ($limit > 0) {
            $query->setMaxResults($limit);
        }

        $query->setFirstResult($offset)
            ->orderBy('t.id', 'DESC');

        $results = $query->getQuery()->getResult();

        return $count ? sizeof($results) : $results;
    }

    public function countUserTricks(User $user): int
    {
        $query = $this->createQueryBuilder('t');
        $query->select('COUNT(t.id)')
            ->where('t.author = :author')
            ->setParameter('author', $user);

        return $query->getQuery()->getSingleScalarResult();
    }

    public function getPaginatedTricks(int $index, int $max): array
    {
        $query = $this->createQueryBuilder('t')
            ->orderBy('t.post_date', 'DESC')
            ->setMaxResults($max)
            ->setFirstResult($index);

        return $query->getQuery()->getScalarResult();
    }

    public function countAll(): int
    {
        $query = $this->createQueryBuilder('t')
            ->where('t.id > 0');

        return count($query->getQuery()->getScalarResult());
    }
}
