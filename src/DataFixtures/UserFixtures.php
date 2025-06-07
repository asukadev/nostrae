<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Admin
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'adminpass'));
        $manager->persist($admin);

        // Organisateurs
        for ($i = 1; $i <= 3; $i++) {
            $orga = new User();
            $orga->setUsername("orga$i");
            $orga->setEmail("orga$i@example.com");
            $orga->setRoles(['ROLE_ORGANIZER']);
            $orga->setPassword($this->hasher->hashPassword($orga, 'password'));
            $manager->persist($orga);
            $this->addReference("organizer_$i", $orga);
        }

        // Utilisateurs simples
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setUsername("user$i");
            $user->setEmail("user$i@example.com");
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $manager->persist($user);
            $this->addReference("user_$i", $user);
        }

        $manager->flush();
    }
}
