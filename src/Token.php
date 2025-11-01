<?php

declare(strict_types=1);

namespace NeuronAI\Providers\GigaChat;

final readonly class Token
{
    public function __construct(
        public string $access_token,
        public int $expires_at,
    ) {
    }
}
