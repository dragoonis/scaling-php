<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\CommandHandler;

use App\Command\AddProductCommand;
use App\Entity\Product;
use App\Projection\ProductProjectionService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AddProductCommandHandler
{
    public function __construct(
        private ProductProjectionService $productProjectionService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(AddProductCommand $command): void
    {
        try {
            $this->entityManager->beginTransaction();

            $product = new Product();
            $product->setName($command->name);
            $product->setDescription($command->description);
            $product->setPrice($command->price);
            $product->setCreatedAt($command->createdAt);

            $this->entityManager->persist($product);
            $this->entityManager->flush();

            $this->productProjectionService->updateProjection($product);

            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->error('AddProduct failed', [
                'command' => $command::class,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
