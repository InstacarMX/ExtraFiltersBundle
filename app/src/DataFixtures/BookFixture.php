<?php

namespace Instacar\ExtraFiltersBundle\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Instacar\ExtraFiltersBundle\App\Entity\Author;
use Instacar\ExtraFiltersBundle\App\Entity\Book;
use Instacar\ExtraFiltersBundle\App\Entity\User;

class BookFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $adminUser = new User('admin', ['ROLE_ADMIN']);
        $normalUser = new User('test', []);
        $manager->persist($adminUser);
        $manager->persist($normalUser);

        $johnDoe = new Author('John Doe');
        $janeDoe = new Author('Jane Doe');
        $manager->persist($johnDoe);
        $manager->persist($janeDoe);

        $manager->persist(new Book(
            'PHP for dummies',
            120,
            new \DateTime('2022-01-01'),
            new \DateTime('2022-01-31'),
            $johnDoe,
            $adminUser,
        ));
        $manager->persist(new Book(
            'How to test',
            180,
            new \DateTime('2022-01-01'),
            new \DateTime('2022-01-31'),
            $janeDoe,
            $adminUser,
        ));
        $manager->persist(new Book(
            'Symfony 6: The right way',
            250,
            new \DateTime('2022-02-01'),
            new \DateTime('2022-02-28'),
            $janeDoe,
            $normalUser,
        ));

        $manager->flush();
    }
}
