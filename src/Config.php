<?php

declare(strict_types=1);

namespace NeuronAI\Providers\GigaChat;

final readonly class Config
{
    public function __construct(
        public string $client_id,
        public string $client_secret,
        public string $model,
        public string $scope,
    ) {
    }
}
