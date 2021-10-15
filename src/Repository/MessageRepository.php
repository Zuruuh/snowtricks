<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function countPostMessages(int $post = 0): int
    {
        $query = $this->createQueryBuilder('m');

        $query->select('COUNT(m.id)');
        if ($post > 0) {
            $query->where('m.post = :post');
            $query->setParameter('post', $post);
        } else {
            $query->where('m.post is NULL');
        }

        return (int) $query->getQuery()->getSingleScalarResult();
    }

    public function getMessages($post = 0, int $limit = 10, int $offset = 0): array
    {
        $query = $this->createQueryBuilder('m')
            ->where('m.id > 0');
        if ($post > 0) {
            $query->andWhere('m.post = :post');
            $query->setParameter('post', $post);
        } else {
            $query->andWhere('m.post is NULL');
        }

        $query->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('m.id', 'DESC');

        return $query->getQuery()->getResult();
    }

    public function countUserMessages(User $user): int
    {
        $query = $this->createQueryBuilder('m');
        $query->select('COUNT(m.id)')
            ->where('m.author = :author')
            ->setParameter('author', $user);

        return $query->getQuery()->getSingleScalarResult();
    }

    // /**
    //  * @return Message[] Returns an array of Message objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Message
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
