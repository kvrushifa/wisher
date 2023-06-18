<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Context;
use OpenAI\Client;
use OpenAI\Responses\Chat\CreateResponse;
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

    public function wish(string $prompt, Context $context): string
    {
        /** @var CreateResponse $result */
        $result = $this->cache->get(md5($prompt), function (ItemInterface $item) use ($prompt, $context) {
            $completions = $this->openaiClient->chat();

            $messages[] = ['role' => 'user', 'content' => $prompt];
            $messages[] = ['role' => 'system', 'content' => (string) $context];

            $result = $completions->create([
                'model' => 'gpt-3.5-turbo-0613',
                'messages' => $messages,
                'functions' => [
                    [
                        "name" => "get_shell_command",
                        "description" => "Receives a one line shell command which executes the user_prompt",
                        "parameters" => [
                            "type" => "object",
                            "properties" => [
                                "command" => [
                                    "type" => "string",
                                    "description" => "A shell command which can run on the specific operating system"
                                ],
                                "context_question" => [
                                    "type" => "string",
                                    "description" => "A question which can be asked to the user if more information are required, can be empty"
                                ],
                            ],
                            "required" => ["command", "context_question"]
                        ]
                    ]
                ],
                'function_call' => ['name' => 'get_shell_command'],
            ]);

            return $result;
        });

        print_r($result->choices);
        return json_decode($result->choices[0]->message->functionCall->arguments)->command;
    }
}