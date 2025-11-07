<?php

declare(strict_types=1);

namespace NeuronAI\Providers\GigaChat;

use GuzzleHttp\Client;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Exceptions\ProviderException;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\GigaChat\Messages\ToolCallMessage;
use NeuronAI\Providers\HasGuzzleClient;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\MessageMapperInterface;
use NeuronAI\Providers\ToolPayloadMapperInterface;
use Psr\SimpleCache\CacheInterface;

class GigaChat implements AIProviderInterface
{
    use AuthenticatesWithApi;
    use HasGuzzleClient;
    use HandleWithTools;
    use HandleChat;
    use HandleStream;

    /**
     * The main URL of the provider API.
     */
    protected string $baseUri = 'https://gigachat.devices.sberbank.ru/api/v1';

    /**
     * System instructions.
     */
    protected ?string $system = null;

    protected MessageMapperInterface $messageMapper;
    protected ToolPayloadMapperInterface $toolPayloadMapper;

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        protected readonly Config $config,
        protected readonly CacheInterface $cache,
        protected readonly bool $verifyTLS = true,
        protected array $parameters = [],
        protected bool $strict_response = false,
        protected ?HttpClientOptions $httpOptions = null,
    ) {
        $config = [
            'base_uri' => \trim($this->baseUri, '/').'/',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'verify' => $this->verifyTLS,
        ];

        if ($this->httpOptions instanceof HttpClientOptions) {
            $config = $this->mergeHttpOptions($config, $this->httpOptions);
        }

        $this->client = new Client($config);
    }

    public function systemPrompt(?string $prompt): AIProviderInterface
    {
        $this->system = $prompt;
        return $this;
    }

    public function messageMapper(): MessageMapperInterface
    {
        return $this->messageMapper ?? $this->messageMapper = new MessageMapper();
    }

    public function toolPayloadMapper(): ToolPayloadMapperInterface
    {
        return $this->toolPayloadMapper ?? $this->toolPayloadMapper = new ToolPayloadMapper();
    }

    /**
     * @param array<string, mixed> $message
     * @throws ProviderException
     */
    protected function createToolCallMessage(array $message): Message
    {
        $tool = $this->findTool($message['function_call']['name'])
            ->setInputs($message['function_call']['arguments'])
            ->setCallId($message['functions_state_id']);

        return new ToolCallMessage('', [$tool]);
    }
}
