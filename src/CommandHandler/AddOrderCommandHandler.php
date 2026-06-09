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

use App\Command\AddOrderCommand;
use App\Entity\Order;
use App\Projection\OrderProjectionService;
use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class AddOrderCommandHandler
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly OrderProjectionService $projectionService,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function __invoke(AddOrderCommand $command): void
    {
        $customer = $this->customerRepository->find($command->customerId);

        if (!$customer) {
            throw new \InvalidArgumentException('Customer not found');
        }

        $orderItems = [];
        $totalAmount = 0.0;
        foreach ($command->items as $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? 1;
            if (!$productId) {
                continue;
            }
            $product = $this->productRepository->find($productId);
            if (!$product) {
                continue;
            }
            $price = (float) $product->getPrice();
            $total = $price * $quantity;
            $orderItems[] = [
                'product_id' => $productId,
                'product_name' => $product->getName(),
                'quantity' => $quantity,
                'price' => $price,
                'total' => $total,
            ];
            $totalAmount += $total;
        }

        $order = new Order();
        $order->setCustomer($customer);
        $order->setOrderNumber($command->orderNumber);
        $order->setTotalAmount(number_format($totalAmount, 2, '.', ''));
        $order->setStatus($command->status);
        $order->setItems($orderItems);
        $order->setCreatedAt($command->createdAt);

        $this->orderRepository->save($order, true);
        $this->projectionService->updateProjection($order);
    }
}
