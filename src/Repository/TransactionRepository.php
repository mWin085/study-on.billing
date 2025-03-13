<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{

    const TRANSACTION_TYPES = [
        0 => 'payment',
        1 => 'deposit',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    //    /**
    //     * @return Transaction[] Returns an array of Transaction objects
    //     */
        public function findByFilter(User $user, $filter = []): array
        {
            $result = $this->createQueryBuilder('t')
                ->andWhere('t.client = :user')
                ->setParameter('user', $user);

            if (isset($filter['course_code'])) {
                $result->join('t.course', 'c')
                    ->andWhere('c.code = :code')
                    ->setParameter('code', $filter['course_code']);
            }


            if (isset($filter['type']) && array_search($filter['type'] , self::TRANSACTION_TYPES) !== false) {
                $result->andWhere('t.type = :type')
                    ->setParameter('type', array_search($filter['type'] , self::TRANSACTION_TYPES));
            }

            if (isset($filter['skip_expired']) && $filter['skip_expired'] != 'false'){
                $result->andWhere('t.validAt > :validAt OR t.validAt IS NULL')
                    ->setParameter('validAt', new \DateTimeImmutable());
            }

            return $result->orderBy('t.id', 'ASC')
                ->getQuery()
                ->getResult();
        }

        public function findRentByDate(\DateInterval $dateInterval)
        {
            $date = (new \DateTime())->add($dateInterval);
            return $this->createQueryBuilder('t')
                ->select('usr.email as email', 'c.title as coursename', 't.validAt as validAt')
                ->innerJoin('t.course', 'c')
                ->innerJoin('t.client', 'usr')
                ->andWhere('c.type = :type')
                ->setParameter('type', array_flip(CourseRepository::COURSE_TYPES)['rent'])
                ->andWhere('t.validAt < :validAt')
                ->setParameter('validAt', $date)
                ->getQuery()->getResult();
        }

    public function findAllByDate(\DateInterval $dateInterval)
    {
        $date = (new \DateTime())->sub($dateInterval);
        return $this->createQueryBuilder('t')
            ->select('c.title as coursename', 't.createdAt as createdAt', 't.value as value', 'c.type as coursetype', 'c.code as coursecode')
            ->innerJoin('t.course', 'c')
            ->andWhere('t.createdAt > :createdAt')
            ->setParameter('createdAt', $date)
            ->getQuery()->getResult();
    }

    //    public function findOneBySomeField($value): ?Transaction
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
