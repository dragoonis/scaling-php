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

use App\Command\DeleteProductCommand;
use App\Projection\ProductProjectionRepository;
use App\Projection\ProductSummaryProjectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DeleteProductCommandHandler
{
    public function __construct(
        private ProductProjectionRepository $projectionRepository,
        private ProductSummaryProjectionRepository $summaryProjectionRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(DeleteProductCommand $command): void
    {
        try {
            $this->entityManager->beginTransaction();

            $product = $this->projectionRepository->find($command->productId);

            if ($product) {
                $this->entityManager->remove($product);
                $this->entityManager->flush();
            }

            $this->projectionRepository->delete($command->productId);
            //            $this->summaryProjectionRepository->remove($command->productId);

            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->error('DeleteProduct failed', [
                'command' => $command::class,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
