<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class PaymentService
{


    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function payCourse(Course $course, User $user)
    {
        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {

            $date = new \DateTimeImmutable();

            $transaction = new Transaction();
            $transaction->setClient($user);
            $transaction->setCourse($course);
            $transaction->setType(0);
            $transaction->setValue($course->getPrice());
            $transaction->setCreatedAt($date);
            if (CourseRepository::COURSE_TYPES[$course->getType()] == 'rent'){
                $transaction->setValidAt($date->modify('+1 week'));
            }

            $user->setBalance($user->getBalance() - $course->getPrice());

            $this->em->persist($transaction);
            $this->em->flush();
            $this->em->getConnection()->commit();

            $result = [
                'code' => Response::HTTP_OK,
                'success' => true,
                'course_type' => $course->getType(),
            ];

            if (CourseRepository::COURSE_TYPES[$course->getType()] == 'rent'){
                $result['expires_at'] = $transaction->getValidAt();
            }

            return $result;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }

    }

    public function deposit($amount, User $user)
    {
        $this->em->getConnection()->beginTransaction(); // suspend auto-commit
        try {

            $date = new \DateTimeImmutable();

            $transaction = new Transaction();
            $transaction->setClient($user);
            $transaction->setType(1);
            $transaction->setValue($amount);
            $transaction->setCreatedAt($date);
            $user->setBalance($user->getBalance() + $amount);

            $this->em->persist($transaction);
            $this->em->flush();
            $this->em->getConnection()->commit();

            $result = [
                'success' => true,
                'amount' => $user->getBalance(),
            ];

            return $result;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }
}