# Neuron GigaChat Provider

[![Packagist](https://img.shields.io/packagist/v/grnsv/neuron-gigachat.svg?style=flat-square)](https://packagist.org/packages/grnsv/neuron-gigachat)
[![License](https://img.shields.io/github/license/grnsv/neuron-gigachat.svg?style=flat-square)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%5E8.2-blue?style=flat-square)](https://www.php.net/)
[![NeuronAI](https://img.shields.io/badge/NeuronAI-Compatible-brightgreen?style=flat-square)](https://github.com/neuron-core/neuron-ai)

---

Неофициальный провайдер **GigaChat** (Сбер) для фреймворка **NeuronAI**.  
Позволяет подключить LLM GigaChat к вашему агенту на базе NeuronAI.

---

## ⚙️ Установка

```bash
composer require grnsv/neuron-gigachat
```

---

## 🔧 Настройка (на примере Laravel)

В `config/services.php` добавьте:

```php
'gigachat' => [
    'client_id' => env('GIGACHAT_CLIENT_ID'),
    'client_secret' => env('GIGACHAT_CLIENT_SECRET'),
    'model' => env('GIGACHAT_MODEL', 'GigaChat'),
    'scope' => env('GIGACHAT_SCOPE', 'GIGACHAT_API_PERS'),
],
```

---

## 🧩 Пример агента

Создаём агента:

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
            background: ['Ты дружелюбный ИИ-агент.'],
        );
    }
}
```

---

## 🧪 Отключение TLS (для разработки)

Если сертификат Минцифры раздражает:

```php
protected function provider(): AIProviderInterface
{
    return new GigaChat(
        config: new Config(...$this->config->get('services.gigachat')),
        cache: $this->cache,
        // отключаем проверку сертификата
        verifyTLS: false,
    );
}
```

---

## 🧠 Контекстная память (сессии)

```php
protected function provider(): AIProviderInterface
{
    return new GigaChat(
        config: new Config(...$this->config->get('services.gigachat')),
        cache: $this->cache,
        // сессия передается в заголовке `X-Session-ID`
        httpOptions: new HttpClientOptions(headers: ['X-Session-ID' => $this->getSessionId()]),
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

---

## 🧰 Тестовая команда

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
            new UserMessage('Когда уже ИИ захватит этот мир?'),
        );

        $this->info($response->getContent());
    }
}
```

---

## 📊 Структурированный вывод (Structured Output)

Пример DTO:

```php
<?php

namespace App\Neuron\DTO;

use NeuronAI\StructuredOutput\SchemaProperty;

class Output
{
    #[SchemaProperty(description: 'Значение вероятности в процентах.', required: true)]
    public float $percent;

    #[SchemaProperty(description: 'Причина выбора такого значения.', required: false)]
    public string $reason;
}
```

Использование в агенте:

```php
final class MyAgent extends Agent
{
    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: ['Ты специалист по правдоподобным предсказаниям. Даешь оценку вероятности события в процентах.'],
        );
    }

    protected function getOutputClass(): string
    {
        return Output::class;
    }
}
```

Пример вызова:

```php
final class TestAgent extends Command
{
    public function handle(MyAgent $agent)
    {
        $response = $agent->structured(
            new UserMessage('Какова вероятность в процентах, что ИИ в этом году захватит мир?'),
        );

        $this->info(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
```

---

## 🤝 Contributing

Если будут вопросы или идеи — PR и issue приветствуются 👋

---

## 🔗 Links

- **NeuronAI** — [github.com/neuron-core/neuron-ai](https://github.com/neuron-core/neuron-ai)  
- **GigaChat API** — [developers.sber.ru](https://developers.sber.ru)
