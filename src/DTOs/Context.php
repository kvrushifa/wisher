<?php

declare(strict_types=1);

namespace App\DTOs;

class Context implements \Stringable
{
    public function __construct(
        public readonly string $os,
        public readonly string $directory,
    )
    {
    }

    public static function createFromDefaults(): self
    {

        return new self(php_uname('s r v'), getcwd());
    }

    public function __toString(): string
    {
        return <<<PHPEOD
users operating system is $this->os
current working dir is $this->directory 
PHPEOD;
    }
}