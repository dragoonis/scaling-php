<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        for ($i = 0; $i < 10000; ++$i) {
            $product = new Product();
            $product->setName($faker->words(3, true));
            $product->setDescription($faker->sentence(12));
            $product->setPrice($faker->randomFloat(2, 1, 1000));
            $product->setCreatedAt(\DateTimeImmutable::createFromInterface($faker->dateTimeBetween('-2 years', 'now')));
            $manager->persist($product);
            if (0 === $i % 1000) {
                $manager->flush();
            }
        }
        $manager->flush();
    }
}
