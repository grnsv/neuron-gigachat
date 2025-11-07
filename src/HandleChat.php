<?php

declare(strict_types=1);

namespace NeuronAI\Providers\GigaChat;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Providers\GigaChat\Messages\Usage;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;

trait HandleChat
{
    protected const STRUCTURED_FUNCTION_NAME = 'return_structured_output';

    protected const STRUCTURED_FUNCTION_DESCRIPTION = 'Функция для возврата структурированного ответа.';

    abstract protected function getToken(): Token;

    public function chat(array $messages): Message
    {
        return $this->chatAsync($messages)->wait();
    }

    public function chatAsync(array $messages): PromiseInterface
    {
        // Include the system prompt
        if (isset($this->system)) {
            \array_unshift($messages, new Message(MessageRole::SYSTEM, $this->system));
        }

        $json = [
            'model' => $this->config->model,
            'messages' => $this->messageMapper()->map($messages),
            ...$this->parameters
        ];

        // Attach tools
        if (!empty($this->tools)) {
            $json['functions'] = $this->toolPayloadMapper()->map($this->tools);
        }

        return $this->client->postAsync('chat/completions', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$this->getToken()->access_token,
                'RqUID' => (string) Uuid::uuid4(),
            ],
            RequestOptions::JSON => $json,
        ])->then(function (ResponseInterface $response) {
            $result = \json_decode($response->getBody()->getContents(), true);

            if ($result['choices'][0]['finish_reason'] === 'function_call') {
                if ($result['choices'][0]['message']['function_call']['name'] === static::STRUCTURED_FUNCTION_NAME) {
                    $content = json_encode($result['choices'][0]['message']['function_call']['arguments'], JSON_UNESCAPED_UNICODE);
                    $response = new AssistantMessage($content);
                } else {
                    $response = $this->createToolCallMessage($result['choices'][0]['message']);
                }
            } else {
                $response = new AssistantMessage($result['choices'][0]['message']['content']);
            }

            if (\array_key_exists('usage', $result)) {
                $response->setUsage(new Usage(...$result['usage']));
            }

            return $response;
        });
    }

    public function structured(array $messages, string $class, array $response_format): Message
    {
        $this->parameters['functions'] ??= [];
        $this->parameters['functions'][] = [
            'name' => static::STRUCTURED_FUNCTION_NAME,
            'description' => static::STRUCTURED_FUNCTION_DESCRIPTION,
            'parameters' => $response_format,
            'required' => ['category_name'],
        ];
        $this->parameters['function_call'] = [
            'name' => static::STRUCTURED_FUNCTION_NAME,
        ];

        return $this->chat($messages);
    }
}
