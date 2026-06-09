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

use App\Command\UpdateProductCommand;
use App\Projection\ProductProjectionService;
use App\Repository\ProductRepository;
use App\Service\ProductSummaryProjectionService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateProductCommandHandler
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductProjectionService $productProjectionService,
        private ProductSummaryProjectionService $summaryProjectionService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateProductCommand $command): void
    {
        try {
            $this->entityManager->beginTransaction();

            $product = $this->productRepository->find($command->id);
            if (!$product) {
                throw new \RuntimeException('Product not found');
            }

            if (null !== $command->name) {
                $product->setName($command->name);
            }
            if (null !== $command->description) {
                $product->setDescription($command->description);
            }
            if (null !== $command->price) {
                $product->setPrice($command->price);
            }
            if (null !== $command->createdAt) {
                $product->setCreatedAt($command->createdAt);
            }

            $this->entityManager->flush();
            $this->entityManager->refresh($product);

            $this->productProjectionService->updateProjection($product);
            //            $this->summaryProjectionService->updateProductSummary($product->getId());

            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->error('UpdateProduct failed', [
                'command' => $command::class,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
