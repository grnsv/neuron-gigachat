<?php

declare(strict_types=1);

namespace NeuronAI\Providers\GigaChat;

use NeuronAI\Providers\GigaChat\Exceptions\AuthenticationException;
use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

trait AuthenticatesWithApi
{
    private const BASE_AUTH_URI = 'https://ngw.devices.sberbank.ru:9443';

    private const AUTH_URI = 'api/v2/oauth';

    private const TOKEN_CACHE_KEY = 'gigachat:access_token';

    protected readonly Config $config;

    protected readonly CacheInterface $cache;

    protected readonly bool $verifyTLS;

    protected Client $authClient;

    protected function getToken(): Token
    {
        return $this->rememberToken($this->authenticate(...));
    }

    protected function rememberToken(Closure $callback): Token
    {
        $token = $this->cache->get(self::TOKEN_CACHE_KEY);
        if ($token !== null) {
            return $token;
        }

        $token = $callback();
        $ttl = $token->expires_at / 1000 - \time();
        $this->cache->set(self::TOKEN_CACHE_KEY, $token, $ttl);

        return $token;
    }

    /**
     * @throws AuthenticationException
     */
    protected function authenticate(): Token
    {
        return $this->getAuthClient()->postAsync(self::AUTH_URI, [
            RequestOptions::HEADERS => ['RqUID' => (string) Uuid::uuid4()],
            RequestOptions::FORM_PARAMS => ['scope' => $this->config->scope],
        ])->then(function (ResponseInterface $response): Token {
            $body = $response->getBody()->getContents();
            try {
                return new Token(...\json_decode($body, true));
            } catch (Throwable) {
                throw new AuthenticationException('Unauthenticated: invalid response '.$body);
            }
        })->otherwise(function ($reason): never {
            if ($reason instanceof Throwable) {
                throw new AuthenticationException('Unauthenticated: '.$reason->getMessage(), previous: $reason);
            }

            throw new AuthenticationException('Unauthenticated: '.$reason);
        })->wait();
    }

    protected function getAuthClient(): Client
    {
        return $this->authClient ??= new Client([
            'base_uri' => self::BASE_AUTH_URI,
            'auth' => [$this->config->client_id, $this->config->client_secret],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'verify' => $this->verifyTLS,
        ]);
    }
}
