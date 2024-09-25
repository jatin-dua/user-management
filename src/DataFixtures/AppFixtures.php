<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create user 1
        $user1 = new User();
        $user1->setName('John');
        $user1->setUsername('admin');
        $user1->setEmail('admin@example.com');
        $user1->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $user1->setPassword($this->passwordHasher->hashPassword($user1, 'admin'));
        $user1->setAddress('admin address');
        $manager->persist($user1);

        // Create user 2
        $user2 = new User();
        $user2->setName('Henry');
        $user2->setUsername('user');
        $user2->setEmail('user@example.com');
        $user2->setRoles(['ROLE_USER']);
        $user2->setPassword($this->passwordHasher->hashPassword($user2, 'user'));
        $user2->setAddress('user address');
        $manager->persist($user2);

        $manager->flush(); // Save all data to the database
    }
}