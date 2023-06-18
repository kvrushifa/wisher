<?php

declare(strict_types=1);

namespace App\Command;

use App\DTOs\Context;
use App\DTOs\HandleShellCommand;
use App\Services\Wisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
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
            ->addArgument(name: 'wish', mode: InputArgument::REQUIRED)
            ->addOption(name: 'dry-run', shortcut: 'd', mode: InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $wish = $input->getArgument('wish');
        $output->writeln('<fg=blue;options=bold>' . $wish . '</>');


        return $this->handleWish($input, $output, $wish);
    }

    private function handleWish(
        InputInterface $input,
        OutputInterface $output,
        string $wish,
    ): int
    {
        $wishResult = $this->wisher->wish($wish, Context::createFromDefaults());
        if (!empty($wishResult->contextCommand)) {
            return $this->specifyContextByCommand($input, $output, $wish, $wishResult);
        }

        if (!empty($wishResult->contextQuestion)) {
            return $this->specifyContextByUserPrompt($input, $output, $wish, $wishResult);
        }

        return $this->handleShellCommand($input, $output, $wishResult);
    }

    private function handleShellCommand(
        InputInterface $input,
        OutputInterface $output,
        HandleShellCommand $handleShellCommand,
    ): int
    {
        $output->writeln('<fg=red;options=bold>' . $handleShellCommand->executableShellCommand . '</>');

        if (true === $input->getOption('dry-run') && false === $this->askForExecutionPrompt($input, $output)) {
            return Command::FAILURE;
        }

        $this->executeCommand($handleShellCommand->executableShellCommand, $output);

        return Command::SUCCESS;
    }

    private function specifyContextByUserPrompt(
        InputInterface $input,
        OutputInterface $output,
        string $wish,
        HandleShellCommand $handleShellCommand,
    ): int
    {
        if (!$this->askForExecutionPrompt($input, $output)) {
            return Command::FAILURE;
        }
        $questionString = rtrim($handleShellCommand->contextQuestion, '?.: ') . ': ';
        $helper = $this->getHelper('question');
        $question = new Question(
            $questionString,
            '',
        );
        $answer = (string) $helper->ask($input, $output, $question);

        $newPrompt = $wish . <<<EOD

---
the user was asked the following question about the context:
$questionString $answer
EOD;

        return $this->handleWish($input, $output, $newPrompt);
    }

    private function specifyContextByCommand(
        InputInterface $input,
        OutputInterface $output,
        string $wish,
        HandleShellCommand $handleShellCommand,
    ): int {
        $output->writeln('<fg=yellow;options=bold>' . $handleShellCommand->contextCommand . '</>');

        if (!$this->askForExecutionPrompt($input, $output)) {
            return Command::FAILURE;
        }

        $commandOutput = $this->executeCommand($handleShellCommand->contextCommand, $output);

        $newPrompt = $wish . <<<EOD

---
running the command "$handleShellCommand->contextCommand" returned the following:
$commandOutput
EOD;

        return $this->handleWish($input, $output, $newPrompt);
    }

    private function askForExecutionPrompt(InputInterface $input, OutputInterface $output): bool
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'Execute? [y/N] ',
            false,
            '/^(y|j)/i'
        );

        return $helper->ask($input, $output, $question);
    }

    public function executeCommand(string $command, OutputInterface $output): string
    {
        $process = Process::fromShellCommandline($command);
        $process->run(fn($type, $data) => $output->writeln($data));

        return $process->getOutput();
    }
}
