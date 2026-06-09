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

use App\Command\AddCustomerCommand;
use App\Command\DeleteCustomerCommand;
use App\Command\GetCustomerCommand;
use App\Command\ListCustomersCommand;
use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/customers')]
final class CustomerController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly CustomerRepository $customerRepository,
    ) {
    }

    #[Route('/db', name: 'list_customers_db', methods: ['GET'])]
    public function listCustomersDb(): JsonResponse
    {
        $customers = $this->customerRepository->findAll();

        return $this->json([
            'customers' => array_map(static function ($c) {
                return [
                    'id' => $c->getId(),
                    'name' => $c->getName(),
                    'email' => $c->getEmail(),
                    'address' => $c->getAddress(),
                    'city' => $c->getCity(),
                    'postal_code' => $c->getPostalCode(),
                    'country' => $c->getCountry(),
                    'created_at' => $c->getCreatedAt()?->format(\DATE_ATOM),
                ];
            }, $customers),
            'total' => \count($customers),
        ]);
    }

    #[Route('/projection', name: 'list_customers_redis', methods: ['GET'])]
    public function listCustomersRedis(): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new ListCustomersCommand());
        $projections = $envelope->last(HandledStamp::class)->getResult();

        return $this->json([
            'customers' => array_map(static fn ($p) => $p->toArray(), $projections),
            'total' => \count($projections),
        ]);
    }

    #[Route('', name: 'add_customer', methods: ['POST'])]
    public function addCustomer(Request $request): JsonResponse
    {
        $command = new AddCustomerCommand(
            $request->get('name'),
            $request->get('email'),
            $request->get('address'),
            $request->get('city'),
            $request->get('postal_code'),
            $request->get('country'),
            new \DateTimeImmutable($request->get('created_at') ?? 'now')
        );
        $this->commandBus->dispatch($command);

        return $this->json(['message' => 'Customer created successfully'], 201);
    }

    #[Route('/db/{id}', name: 'get_customer_db', methods: ['GET'])]
    public function getCustomerDb(int $id): JsonResponse
    {
        $customer = $this->customerRepository->find($id);
        if (!$customer) {
            return $this->json(['error' => 'Customer not found'], 404);
        }

        return $this->json([
            'customer' => [
                'id' => $customer->getId(),
                'name' => $customer->getName(),
                'email' => $customer->getEmail(),
                'address' => $customer->getAddress(),
                'city' => $customer->getCity(),
                'postal_code' => $customer->getPostalCode(),
                'country' => $customer->getCountry(),
                'created_at' => $customer->getCreatedAt()?->format(\DATE_ATOM),
            ],
        ]);
    }

    #[Route('/projection/{id}', name: 'get_customer_redis', methods: ['GET'])]
    public function getCustomerRedis(int $id): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new GetCustomerCommand($id));
        $customer = $envelope->last(HandledStamp::class)->getResult();
        if (!$customer) {
            return $this->json(['error' => 'Customer not found'], 404);
        }

        return $this->json(['customer' => $customer]);
    }

    #[Route('/{id}', name: 'delete_customer', methods: ['DELETE'])]
    public function deleteCustomer(int $id): JsonResponse
    {
        $command = new DeleteCustomerCommand($id);
        $this->commandBus->dispatch($command);

        return $this->json(['message' => 'Customer deleted successfully']);
    }
}
