# Wisher

Wisher is a PHP CLI application that uses OpenAI's API to generate and execute shell commands based on prompts.

![Example Prompt](docs/example-prompt.png)

## Prerequisites

- PHP version 8.1 or higher must be installed.
- Run `composer install` to install the project dependencies.

## Usage

To make a prompt and generate shell commands, use the following command:

```bash
bin/console wisher:wish <prompt>
```

Replace `<prompt>` with your desired prompt for generating the shell commands.

## TODO List

- [ ] Make a runnable PHAR file for easy distribution and usage.
- [ ] Error handling.
- [ ] More context eventually.
- [ ] Own service(s) for the execution of shell commands
