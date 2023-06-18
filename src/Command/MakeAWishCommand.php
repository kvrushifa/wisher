<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeAWishCommand extends Command
{
    protected static $defaultName = 'wisher:wish';

    protected function configure()
    {
        $this
            ->addArgument('wish', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $wish = $input->getArgument('wish');


        return Command::SUCCESS;
    }
}
