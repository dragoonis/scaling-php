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

use App\Command\AddOrderCommand;
use App\Command\DeleteOrderCommand;
use App\Command\GetOrderCommand;
use App\Command\ListOrdersCommand;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/orders')]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly OrderRepository $orderRepository,
    ) {
    }

    #[Route('/db', name: 'list_orders_db', methods: ['GET'])]
    public function listOrdersDb(): JsonResponse
    {
        $orders = $this->orderRepository->findAll();

        return $this->json([
            'orders' => array_map(static function ($o) {
                return [
                    'id' => $o->getId(),
                    'customer_id' => $o->getCustomer()?->getId(),
                    'customer_name' => $o->getCustomer()?->getName(),
                    'order_number' => $o->getOrderNumber(),
                    'total_amount' => $o->getTotalAmount(),
                    'status' => $o->getStatus(),
                    'items' => $o->getItems(),
                    'created_at' => $o->getCreatedAt()?->format(\DATE_ATOM),
                ];
            }, $orders),
            'total' => \count($orders),
        ]);
    }

    #[Route('/projection', name: 'list_orders_redis', methods: ['GET'])]
    public function listOrdersRedis(): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new ListOrdersCommand());
        $projections = $envelope->last(HandledStamp::class)->getResult();

        return $this->json([
            'orders' => array_map(static fn ($p) => $p->toArray(), $projections),
            'total' => \count($projections),
        ]);
    }

    #[Route('', name: 'add_order', methods: ['POST'])]
    public function addOrder(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $items = $data['items'] ?? [];
        $command = new AddOrderCommand(
            $data['customer_id'] ?? null,
            $data['order_number'] ?? '',
            $data['total_amount'] ?? '',
            $data['status'] ?? '',
            $items,
            isset($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : new \DateTimeImmutable('now')
        );
        $this->commandBus->dispatch($command);

        return $this->json(['message' => 'Order created successfully'], 201);
    }

    #[Route('/db/{id}', name: 'get_order_db', methods: ['GET'])]
    public function getOrderDb(int $id): JsonResponse
    {
        $order = $this->orderRepository->find($id);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        return $this->json([
            'order' => [
                'id' => $order->getId(),
                'customer_id' => $order->getCustomer()?->getId(),
                'customer_name' => $order->getCustomer()?->getName(),
                'order_number' => $order->getOrderNumber(),
                'total_amount' => $order->getTotalAmount(),
                'status' => $order->getStatus(),
                'items' => $order->getItems(),
                'created_at' => $order->getCreatedAt()?->format(\DATE_ATOM),
            ],
        ]);
    }

    #[Route('/projection/{id}', name: 'get_order_redis', methods: ['GET'])]
    public function getOrderRedis(int $id): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new GetOrderCommand($id));
        $order = $envelope->last(HandledStamp::class)->getResult();
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        return $this->json(['order' => $order]);
    }

    #[Route('/{id}', name: 'delete_order', methods: ['DELETE'])]
    public function deleteOrder(int $id): JsonResponse
    {
        $command = new DeleteOrderCommand($id);
        $this->commandBus->dispatch($command);

        return $this->json(['message' => 'Order deleted successfully']);
    }
}
