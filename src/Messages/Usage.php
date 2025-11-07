<?php

declare(strict_types=1);

namespace NeuronAI\Providers\GigaChat\Messages;

class Usage extends \NeuronAI\Chat\Messages\Usage
{
    public function __construct(
        int $prompt_tokens, // Токены запроса, после вычитания кэшированных токенов
        int $completion_tokens, // Токены, потраченные на генерацию ответа
        public readonly int $precached_prompt_tokens, // Кэшированные токены из предыдущих запросов. Вычитаются из общего количества токенов, подлежащих тарификации
        public readonly int $total_tokens, // Общее число токенов, подлежащих тарификации, после вычитания кэшированных токенов
    ) {
        parent::__construct(
            inputTokens: $prompt_tokens,
            outputTokens: $completion_tokens,
        );
    }

    public function getTotal(): int
    {
        return $this->total_tokens;
    }

    /**
     * @return array<string, int>
     */
    public function jsonSerialize(): array
    {
        return [
            'prompt_tokens' => $this->inputTokens,
            'completion_tokens' => $this->outputTokens,
            'precached_prompt_tokens' => $this->precached_prompt_tokens,
            'total_tokens' => $this->total_tokens,
        ];
    }
}
