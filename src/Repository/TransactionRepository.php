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
