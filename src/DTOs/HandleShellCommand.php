<?php

declare(strict_types=1);

namespace App\DTOs;

class HandleShellCommand
{
    public function __construct(
        public readonly string $executableShellCommand,
        public readonly ?string $contextQuestion = null,
        public readonly ?string $contextCommand = null,
    )
    {
    }
}