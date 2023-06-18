<?php

declare(strict_types=1);

namespace App\DTOs;

class Context implements \Stringable
{
    public function __construct(
        public readonly string $os
    )
    {
    }

    public static function createFromDefaults(): self
    {

        return new self(php_uname('s r v'));
    }

    public function __toString(): string
    {
        return sprintf('Users operating system information: %s', $this->os);
    }
}