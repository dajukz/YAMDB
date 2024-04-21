<?php

namespace App\DataFixtures;

use App\Entity\Movie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        //TODO: Test uithalen
        for ($i = 0; $i < 3; $i++) {

            $movie = new Movie();

            $movie->setTitle('product '.$i);
            $movie->setDirector('something');
            $movie->setReleaseDate(2024);
            $movie->setOverview('lorem ipsum dolor sit amet');

            $manager->persist($movie);
        }

        $manager->flush();
    }
}
