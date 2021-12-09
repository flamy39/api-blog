<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class PostFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create("fr_FR");

        for ($i=1; $i <= 50; $i++) {
            $post = new Post();
            $post->setTitle($faker->sentence(5,true))
                ->setContent($faker->paragraphs(4,true))
                ->setCreatedAt($faker->dateTimeBetween('-6 months'));
            $userReference = "user_".$faker->numberBetween(1,10);
            $post->setUser($this->getReference($userReference));
            $manager->persist($post);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return array(UserFixtures::class);
    }
}
