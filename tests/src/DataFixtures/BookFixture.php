<?php

namespace Instacar\ExtraFiltersBundle\Test\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Instacar\ExtraFiltersBundle\Test\Entity\Author;
use Instacar\ExtraFiltersBundle\Test\Entity\Book;

class BookFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $johnDoe = new Author('John Doe');
        $janeDoe = new Author('Jane Doe');
        $manager->persist($johnDoe);
        $manager->persist($janeDoe);

        $manager->persist(new Book('PHP for dummies', new \DateTime('2022-01-01'), new \DateTime('2022-01-31'), $johnDoe));
        $manager->persist(new Book('How to test', new \DateTime('2022-01-01'), new \DateTime('2022-01-31'), $janeDoe));
        $manager->persist(new Book('Symfony 6: The right way', new \DateTime('2022-02-01'), new \DateTime('2022-02-28'), $janeDoe));

        $manager->flush();
    }
}
