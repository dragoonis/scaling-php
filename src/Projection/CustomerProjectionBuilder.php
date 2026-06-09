<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Projection;

use App\Entity\Customer;

final class CustomerProjectionBuilder
{
    public function build(Customer $customer): CustomerProjection
    {
        return new CustomerProjection(
            $customer->getId(),
            $customer->getName(),
            $customer->getEmail(),
            $customer->getAddress(),
            $customer->getCity(),
            $customer->getPostalCode(),
            $customer->getCountry(),
            $customer->getCreatedAt()
        );
    }
}
