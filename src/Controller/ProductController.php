<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Command\AddProductCommand;
use App\Command\DeleteProductCommand;
use App\Command\GetProductCommand;
use App\Command\ListProductsCommand;
use App\Command\UpdateProductCommand;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/products')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    #[Route('/projection', name: 'list_products_redis', methods: ['GET'])]
    public function listProductsRedis(): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new ListProductsCommand());
        $projections = $envelope->last(HandledStamp::class)->getResult();

        return $this->json([
            'products' => array_map(static fn ($product) => $product->toArray(), $projections),
            'total' => \count($projections),
        ]);
    }

    #[Route('', name: 'add_product', methods: ['POST'])]
    public function addProduct(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $command = new AddProductCommand(
            $data['name'],
            $data['description'],
            $data['price'],
            new \DateTimeImmutable($data['created_at'] ?? 'now')
        );
        $this->commandBus->dispatch($command);

        return $this->json(['message' => 'Product created successfully'], 201);
    }

    #[Route('/projection/{id}', name: 'get_product_redis', methods: ['GET'])]
    public function getProductRedis(int $id): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new GetProductCommand($id));
        $product = $envelope->last(HandledStamp::class)->getResult();
        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        return $this->json(['product' => $product]);
    }

    #[Route('/{id}', name: 'delete_product', methods: ['DELETE'])]
    public function deleteProduct(int $id): JsonResponse
    {
        $command = new DeleteProductCommand($id);
        $this->commandBus->dispatch($command);

        return $this->json(['message' => 'Product deleted successfully']);
    }

    #[Route('/{id}', name: 'update_product', methods: ['PUT'])]
    public function updateProduct(int $id, Request $request): JsonResponse
    {
        $data = $request->toArray();
        $command = new UpdateProductCommand(
            $id,
            $data['name'] ?? null,
            $data['description'] ?? null,
            $data['price'] ?? null,
            isset($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : null
        );
        $this->commandBus->dispatch($command);

        return $this->json(['message' => 'Product updated successfully']);
    }

    #[Route('/db', name: 'list_products_db', methods: ['GET'])]
    public function listProductsDb(ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findAll();

        return $this->json([
            'products' => array_map(static function ($p) {
                return [
                    'id' => $p->getId(),
                    'name' => $p->getName(),
                    'description' => $p->getDescription(),
                    'price' => $p->getPrice(),
                    'created_at' => $p->getCreatedAt()?->format(\DATE_ATOM),
                ];
            }, $products),
            'total' => \count($products),
        ]);
    }

    #[Route('/db/{id}', name: 'get_product_db', methods: ['GET'])]
    public function getProductDb(int $id, ProductRepository $productRepository): JsonResponse
    {
        $product = $productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        return $this->json([
            'product' => [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'created_at' => $product->getCreatedAt()?->format(\DATE_ATOM),
            ],
        ]);
    }
}
