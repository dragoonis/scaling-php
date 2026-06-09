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

use App\Projection\ProductProjectionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:rebuild-product-projections',
    description: 'Rebuilds all product DB projections from the main Product table.',
)]
class RebuildProductProjectionsCommand extends Command
{
    public function __construct(
        private readonly ProductProjectionService $redisProjectionService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Rebuilding Redis product projections...');
        $this->redisProjectionService->rebuildAll();
        $output->writeln('Done!');

        return Command::SUCCESS;
    }
}
