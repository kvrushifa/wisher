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
                        "description" => "Gets a one line command for the user input",
                        "parameters" => [
                            "type" => "object",
                            "properties" => [
                                "command" => [
                                    "type" => "string",
                                    "description" => "Something a user wants to do on the shell, e.g. fetch all txt files in the directory"
                                ],
                            ],
                            "required" => ["user_prompt"]
                        ]
                    ]
                ],
                'function_call' => ['name' => 'get_shell_command'],
            ]);

            return $result;
        });

        return json_decode($result->choices[0]->message->functionCall->arguments)->command;
    }
}