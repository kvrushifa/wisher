<?php

declare(strict_types=1);

namespace App\Command;

use App\DTOs\Context;
use App\Services\Wisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeAWishCommand extends Command
{
    protected static $defaultName = 'wisher:wish';

    public function __construct(private readonly Wisher $wisher)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument('wish', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $wish = $input->getArgument('wish');

        $command = $this->wisher->wish($wish, Context::createFromDefaults());
        $output->writeln($command);

        $output->writeln('Executing...');

        // auslagern in service
        while (@ ob_end_flush()); // end all output buffers if any

        $proc = popen($command, 'r');
        while (!feof($proc))
        {
            echo fread($proc, 4096);
            @ flush();
        }

        return Command::SUCCESS;
    }
}
