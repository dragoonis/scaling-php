<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\Product;
use App\Projection\CustomerProjectionService;
use App\Projection\OrderProjectionService;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-database',
    description: 'Seed the database with products, customers, and orders',
)]
final class SeedDatabaseCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CustomerProjectionService $customerProjectionService,
        private readonly OrderProjectionService $orderProjectionService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $faker = Factory::create();

        $io->title('Seeding Database');

        // Clear existing data
        $io->section('Clearing existing data...');
        $this->entityManager->createQuery('DELETE FROM App\Entity\Order')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Customer')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Product')->execute();
        $this->entityManager->flush();

        // Create Products
        $io->section('Creating 10,000 products...');
        $products = [];
        for ($i = 0; $i < 10000; ++$i) {
            $product = new Product();
            $product->setName($faker->words(3, true));
            $product->setDescription($faker->paragraph());
            $product->setPrice($faker->randomFloat(2, 1, 1000));
            $product->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year', 'now')));

            $this->entityManager->persist($product);
            $products[] = $product;

            if (($i + 1) % 100 === 0) {
                $this->entityManager->flush();
                $io->text('Created '.($i + 1).' products');
            }
        }
        $this->entityManager->flush();
        $io->success('Created 10,000 products');

        // Create Customers
        $io->section('Creating 5,000 customers...');
        $customers = [];
        for ($i = 0; $i < 5000; ++$i) {
            $customer = new Customer();
            $customer->setName($faker->name());
            $customer->setEmail($faker->unique()->safeEmail());
            $customer->setAddress($faker->streetAddress());
            $customer->setCity($faker->city());
            $customer->setPostalCode($faker->postcode());
            $customer->setCountry($faker->country());
            $customer->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year', 'now')));

            $this->entityManager->persist($customer);
            $customers[] = $customer;

            if (($i + 1) % 1000 === 0) {
                $this->entityManager->flush();
                $io->text('Created '.($i + 1).' customers');
            }
        }
        $this->entityManager->flush();
        $io->success('Created 5,000 customers');

        // Create Orders (2 per customer)
        $io->section('Creating 20,000 orders (2 per customer)...');
        $orderStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        for ($i = 0; $i < 5000; ++$i) {
            $customer = $customers[$i];

            // Create 2 orders per customer
            for ($j = 0; $j < 2; ++$j) {
                $order = new Order();
                $order->setCustomer($customer);
                $order->setOrderNumber('ORD-'.mb_str_pad($i * 2 + $j + 1, 6, '0', \STR_PAD_LEFT));
                $order->setStatus($faker->randomElement($orderStatuses));
                $order->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-6 months', 'now')));

                // Add 1-5 random products to the order
                $orderItems = [];
                $totalAmount = 0;
                $numItems = $faker->numberBetween(1, 5);

                for ($k = 0; $k < $numItems; ++$k) {
                    $product = $faker->randomElement($products);
                    $quantity = $faker->numberBetween(1, 5);
                    $price = $product->getPrice();
                    $itemTotal = $price * $quantity;
                    $totalAmount += $itemTotal;

                    $orderItems[] = [
                        'product_id' => $product->getId(),
                        'product_name' => $product->getName(),
                        'quantity' => $quantity,
                        'price' => $price,
                        'total' => $itemTotal,
                    ];
                }

                $order->setItems($orderItems);
                $order->setTotalAmount(number_format($totalAmount, 2, '.', ''));

                $this->entityManager->persist($order);
            }

            if (($i + 1) % 1000 === 0) {
                $this->entityManager->flush();
                $io->text('Created '.(($i + 1) * 2).' orders');
            }
        }
        $this->entityManager->flush();
        $io->success('Created 20,000 orders');

        $io->success('Database seeding completed successfully!');
        $io->table(['Entity', 'Count'], [
            ['Products', '10,000'],
            ['Customers', '5,000'],
            ['Orders', '20,000'],
        ]);

        // Rebuild projections
        $io->section('Rebuilding projections...');
        $this->customerProjectionService->rebuildAll();
        $this->orderProjectionService->rebuildAll();
        $io->success('Projections rebuilt successfully!');

        return Command::SUCCESS;
    }
}
