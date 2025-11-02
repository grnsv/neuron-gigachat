# Neuron GigaChat Provider

Неофициальный провайдер для **GigaChat** от Сбера для фреймворка **NeuronAI**.  
Позволяет подключить LLM GigaChat к вашему агенту на базе NeuronAI без лишней боли (в процессе разработки 🙂).

> Packagist: https://packagist.org/packages/grnsv/neuron-gigachat  
> NeuronAI: https://github.com/neuron-core/neuron-ai  
> GigaChat: https://developers.sber.ru

## Установка

```bash
composer require grnsv/neuron-gigachat
```

## Настройка на примере Laravel

В `config/services.php` добавьте:

```php
'gigachat' => [
    'client_id' => env('GIGACHAT_CLIENT_ID'),
    'client_secret' => env('GIGACHAT_CLIENT_SECRET'),
    'model' => env('GIGACHAT_MODEL', 'GigaChat'),
    'scope' => env('GIGACHAT_SCOPE', 'GIGACHAT_API_PERS'),
],
```

## Пример агента

Создаем агента:

```bash
php vendor/bin/neuron make:agent App\\Neuron\\Agents\\MyAgent
```

Пример класса:

```php
<?php declare(strict_types=1);

namespace App\Neuron\Agents;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\GigaChat\Config;
use NeuronAI\Providers\GigaChat\GigaChat;
use NeuronAI\SystemPrompt;

final class MyAgent extends Agent
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly CacheRepository $cache,
    ) {}

    protected function provider(): AIProviderInterface
    {
        return new GigaChat(
            config: new Config(...$this->config->get('services.gigachat')),
            cache: $this->cache,
        );
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: ['Ты дружелюбный ИИ-агент'],
        );
    }
}
```

## Отключение TLS (для разработки)

Если сертификат Минцифры раздражает:

```php
protected function provider(): AIProviderInterface
{
    return new GigaChat(
        config: new Config(...$this->config->get('services.gigachat')),
        cache: $this->cache,
        verifyTLS: false, // отключаем проверку сертификата
    );
}
```

## Поддержка сессий (контекстная память)

```php
protected function provider(): AIProviderInterface
{
    return new GigaChat(
        config: new Config(...$this->config->get('services.gigachat')),
        cache: $this->cache,
        httpOptions: new HttpClientOptions(headers: ['X-Session-ID' => $this->getSessionId()]), // сессия передается в заголовке `X-Session-ID`
    );
}

// здесь ваш механизм хранения сессий
private function getSessionId(): string
{
    return $this->cache->remember(
        'my_agent:session_id',
        now()->endOfWeek(),
        fn (): string => (string) Str::uuid(),
    );
}
```

## Тестовая команда

```bash
php artisan make:command TestAgent
```

```php
<?php

namespace App\Console\Commands;

use App\Neuron\Agents\MyAgent;
use Illuminate\Console\Command;
use NeuronAI\Chat\Messages\UserMessage;

final class TestAgent extends Command
{
    protected $signature = 'app:test-agent';
    protected $description = 'Test NeuronAI + GigaChat agent';

    public function handle(MyAgent $agent)
    {
        $response = $agent->chat(
            new UserMessage('Когда уже ИИ захватит этот мир?')
        );

        $this->info($response->getContent());
    }
}
```

Если будут вопросы или идеи — PR и issue приветствуются 👋
