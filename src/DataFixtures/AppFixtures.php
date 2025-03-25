<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);


        $admin = new User();
        $password = $this->hasher->hashPassword($admin, 'adminadmin');
        $admin->setEmail('admin@admin.com');
        $admin->setPassword($password);
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $admin->setBalance($_ENV['BALANCE']);
        $manager->persist($admin);
        $manager->flush();

        $user = new User();
        $password = $this->hasher->hashPassword($user, 'useruser');
        $user->setEmail('user@user.com');
        $user->setPassword($password);
        $user->setRoles(['ROLE_USER']);
        $user->setBalance($_ENV['BALANCE']);
        $manager->persist($user);
        $manager->flush();

        $course1 = new Course();
        $course1->setType(0);
        $course1->setCode('course_1');
        $course1->setTitle('Древняя Греция: от мифов к демократии');
        $manager->persist($course1);
        $manager->flush();

        $course2 = new Course();
        $course2->setType(1);
        $course2->setCode('course_2');
        $course2->setTitle('Средневековая Европа: войны, крестовые походы и культурные преобразования');
        $course2->setPrice(299);
        $manager->persist($course2);
        $manager->flush();

        $course3 = new Course();
        $course3->setType(2);
        $course3->setCode('course_3');
        $course3->setTitle('Великие географические открытия и их последствия');
        $course3->setPrice(1299);
        $manager->persist($course3);
        $manager->flush();

        $course4 = new Course();
        $course4->setType(1);
        $course4->setCode('course_212');
        $course4->setTitle('Тест');
        $course4->setPrice(1299);
        $manager->persist($course4);
        $manager->flush();

        $date = new \DateTimeImmutable();
        $tomorrow = (new \DateTimeImmutable())->add(new \DateInterval('P1D'));
        $dateOneDay = (new \DateTimeImmutable())->sub(new \DateInterval('P1D'));
        $dateOneWeek = (new \DateTimeImmutable())->sub(new \DateInterval('P7D'));
        $dateOneMonth = (new \DateTimeImmutable())->sub(new \DateInterval('P1M'));

        $transaction = new Transaction();
        $transaction->setType(0);
        $transaction->setValue(299);
        $transaction->setClient($admin);
        $transaction->setCourse($course2);
        $transaction->setValidAt($tomorrow);
        $transaction->setCreatedAt($tomorrow->sub(new \DateInterval('P7D')));
        $manager->persist($transaction);
        $manager->flush();

        $transaction = new Transaction();
        $transaction->setType(0);
        $transaction->setValue(299);
        $transaction->setClient($admin);
        $transaction->setCourse($course2);
        $transaction->setValidAt($dateOneWeek);
        $transaction->setCreatedAt($dateOneWeek->sub(new \DateInterval('P7D')));
        $manager->persist($transaction);
        $manager->flush();

        $transaction = new Transaction();
        $transaction->setType(0);
        $transaction->setValue(299);
        $transaction->setClient($admin);
        $transaction->setCourse($course2);
        $transaction->setValidAt($dateOneMonth);
        $transaction->setCreatedAt($dateOneMonth->sub(new \DateInterval('P7D')));
        $manager->persist($transaction);
        $manager->flush();


        $transaction = new Transaction();
        $transaction->setType(0);
        $transaction->setValue(1299);
        $transaction->setClient($admin);
        $transaction->setCourse($course3);
        $transaction->setCreatedAt($dateOneWeek);
        $manager->persist($transaction);
        $manager->flush();

        $transaction = new Transaction();
        $transaction->setType(0);
        $transaction->setValue(299);
        $transaction->setClient($user);
        $transaction->setCourse($course2);
        $transaction->setValidAt($tomorrow);
        $transaction->setCreatedAt($tomorrow->sub(new \DateInterval('P7D')));
        $manager->persist($transaction);
        $manager->flush();

        $transaction = new Transaction();
        $transaction->setType(0);
        $transaction->setValue(299);
        $transaction->setClient($user);
        $transaction->setCourse($course2);
        $transaction->setValidAt($dateOneWeek);
        $transaction->setCreatedAt($dateOneWeek->sub(new \DateInterval('P7D')));
        $manager->persist($transaction);
        $manager->flush();


        $transaction = new Transaction();
        $transaction->setType(0);
        $transaction->setValue(1299);
        $transaction->setClient($user);
        $transaction->setCourse($course3);
        $transaction->setCreatedAt($dateOneWeek);
        $manager->persist($transaction);
        $manager->flush();

    }
}
