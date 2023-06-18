<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Context;
use App\DTOs\HandleShellCommand;
use OpenAI\Client;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Responses\Chat\CreateResponseFunctionCall;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class Wisher implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly Client $openaiClient,
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer,
    )
    {
    }

    public function wish(string $prompt, Context $context): HandleShellCommand
    {
        /** @var CreateResponse $result */
        $result = $this->cache->get(md5($prompt), function (ItemInterface $item) use ($prompt, $context) {
            $item->expiresAfter(1);

            $completions = $this->openaiClient->chat();

            $messages[] = [
                'role' => 'user',
                'content' => $prompt,
            ];
            $messages[] = [
                'role' => 'system',
                'content' => <<<EOD
You know the following about the users system:
$context
EOD,
            ];

            return $completions->create([
                'model' => 'gpt-3.5-turbo-0613',
                'messages' => $messages,
                'functions' => [
                    [
                        "name" => "handle_shell_command",
                        "description" => <<<EOD
Handles shell commands which can be executed on system of the users. 
If the prompt is unclear, too vague or has a missing part always ask the user for more context using the contextQuestion.
The answers of the questions can be new instruction or safe values. 
Ask the user any question or execute commands on the users system to obtain any necessary information for the task.
EOD,
                        "parameters" => [
                            "type" => "object",
                            "properties" => [
                                "executableShellCommand" => [
                                    "type" => "string",
                                    "description" => "Shell commands that can be executed on the users system without further information, multiple shell commands can be concatenated by && or |"
                                ],
                                "contextQuestion" => [
                                    "type" => "string",
                                    "description" => "A question which can be asked to the user if more information is needed, f.e. format of an output, a directory etc."
                                ],
                                "contextCommand" => [
                                    "type" => "string",
                                    "description" => "A bash command that will be run on the users system in order to gather more context f.e. ls. The output will then be returned as additional context."
                                ],
                            ],
                            "required" => ["executableShellCommand", "contextQuestion", "contextCommand"],
                        ]
                    ]
                ],
                'function_call' => ['name' => 'handle_shell_command'],
            ]);
        });

        $this->logger->debug($prompt);

        return $this->deserializeResult($result->choices[0]->message->functionCall);
    }

    private function deserializeResult(CreateResponseFunctionCall $call): HandleShellCommand
    {

        return $this->serializer->deserialize($call->arguments, HandleShellCommand::class, 'json');
    }
}