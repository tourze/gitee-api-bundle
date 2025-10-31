<?php

declare(strict_types=1);

namespace GiteeApiBundle\Tests\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * 简单的HttpClient测试桩，用于替代复杂的匿名类
 */
class TestHttpClientStub implements HttpClientInterface
{
    /** @var array<int, ResponseInterface> */
    private array $responses = [];

    private int $callCount = 0;

    /** @param array<int, ResponseInterface> $responses */
    public function setResponses(array $responses): void
    {
        $this->responses = $responses;
        $this->callCount = 0;
    }

    /** @phpstan-ignore missingType.iterableValue */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $response = $this->responses[$this->callCount] ?? null;
        ++$this->callCount;
        if (null === $response) {
            throw new \RuntimeException('Unexpected request: ' . $method . ' ' . $url);
        }

        return $response;
    }

    /**
     * @param ResponseInterface|iterable<ResponseInterface> $responses
     */
    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        throw new \LogicException('Stream method not implemented for testing');
    }

    /** @phpstan-ignore missingType.iterableValue */
    public function withOptions(array $options): static
    {
        return $this;
    }
}
