<?php

namespace App\DataFixtures;

use App\Entity\Course;
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


        $user = new User();
        $password = $this->hasher->hashPassword($user, 'adminadmin');
        $user->setEmail('admin@admin.com');
        $user->setPassword($password);
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $user->setBalance($_ENV['BALANCE']);
        $manager->persist($user);
        $manager->flush();

        $user = new User();
        $password = $this->hasher->hashPassword($user, 'useruser');
        $user->setEmail('user@user.com');
        $user->setPassword($password);
        $user->setRoles(['ROLE_USER']);
        $user->setBalance($_ENV['BALANCE']);
        $manager->persist($user);
        $manager->flush();

        $course = new Course();
        $course->setType(0);
        $course->setCode('course_1');
        $manager->persist($course);
        $manager->flush();

        $course = new Course();
        $course->setType(1);
        $course->setCode('course_2');
        $course->setPrice(299);
        $manager->persist($course);
        $manager->flush();

        $course = new Course();
        $course->setType(2);
        $course->setCode('course_3');
        $course->setPrice(1299);
        $manager->persist($course);
        $manager->flush();
    }
}
