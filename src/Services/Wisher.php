<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Context;
use App\DTOs\HandleShellCommand;
use App\DTOs\SpecifyContextCommand;
use OpenAI\Client;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Responses\Chat\CreateResponseFunctionCall;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class Wisher
{
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
                'content' => 'You will generate a shell command which can be run in the following context:' . (string) $context . '. If the user input is to vague, ask for more context in by using the contextQuestion',
            ];

            return $completions->create([
                'model' => 'gpt-3.5-turbo-0613',
                'messages' => $messages,
                'functions' => [
                    [
                        "name" => "handle_shell_command",
                        "description" => <<<EOD
Receives a one line shell command as value for executableShellCommand which can be executed on the user's system.
If the prompt is unclear, too vague or has a missing part always ask the user for more context using the contextQuestion.
Note that answered of the question's are not always deterministic
EOD,
                        "parameters" => [
                            "type" => "object",
                            "properties" => [
                                "executableShellCommand" => [
                                    "type" => "string",
                                    "description" => "A shell command that can be executed without further information."
                                ],
                                "contextQuestion" => [
                                    "type" => "string",
                                    "description" => "A question which can be asked to the user if more information are required, can be empty, f.e. format of an output, a directory etc."
                                ],
                            ],
                            "required" => ["executableShellCommand", "contextQuestion"],
                        ]
                    ]
                ],
                'function_call' => ['name' => 'handle_shell_command'],
            ]);
        });


        return $this->deserializeResult($result->choices[0]->message->functionCall);
    }

    private function deserializeResult(CreateResponseFunctionCall $call): HandleShellCommand
    {

        return $this->serializer->deserialize($call->arguments, HandleShellCommand::class, 'json');
    }
}