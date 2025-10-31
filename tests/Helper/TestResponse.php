<?php

declare(strict_types=1);

namespace GiteeApiBundle\Tests\Helper;

use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * 测试用 Response 实现
 */
class TestResponse implements ResponseInterface
{
    /** @var array<string, mixed> */
    private array $data;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /** @return array<string, mixed> */
    public function toArray(bool $throw = true): array
    {
        return $this->data;
    }

    public function getStatusCode(): int
    {
        return 200;
    }

    /** @return array<string, list<string>> */
    public function getHeaders(bool $throw = true): array
    {
        return [];
    }

    public function getContent(bool $throw = true): string
    {
        $json = json_encode($this->data);

        return false !== $json ? $json : '{}';
    }

    public function cancel(): void
    {
    }

    public function getInfo(?string $type = null): mixed
    {
        return null;
    }
}
