<?php

declare(strict_types=1);

namespace App\Command;

use App\DTOs\Context;
use App\Services\Wisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

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

        $process = Process::fromShellCommandline($command);
        $process->run(fn($type, $data) => $output->writeln($data));

        return Command::SUCCESS;
    }
}
