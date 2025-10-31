<?php

declare(strict_types=1);

namespace GiteeApiBundle\Tests\Service;

use Psr\SimpleCache\CacheInterface;

/**
 * @internal
 */
final class MockCacheInterface implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $storage = [];

    /** @var array<int, array{string, mixed, int|null}>|string[] */
    private array $setExpectations = [];

    /** @var array<int, array{string, mixed}> */
    private array $getExpectations = [];

    /** @var array<int, string> */
    private array $deleteExpectations = [];

    private int $setCalls = 0;

    private int $getCalls = 0;

    private int $deleteCalls = 0;

    public function expectSet(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->setExpectations[] = [$key, $value, $ttl];
    }

    public function expectGet(string $key, mixed $returnValue): void
    {
        $this->getExpectations[] = [$key, $returnValue];
    }

    public function expectDelete(string $key): void
    {
        $this->deleteExpectations[] = $key;
    }

    public function expectNeverSet(): void
    {
        $this->setExpectations = ['NEVER'];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($this->getExpectations[$this->getCalls])) {
            [$expectedKey, $returnValue] = $this->getExpectations[$this->getCalls];
            if ($key === $expectedKey) {
                ++$this->getCalls;

                return $returnValue;
            }
        }

        return $this->storage[$key] ?? $default;
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        if ($this->setExpectations === ['NEVER']) {
            throw new \RuntimeException('Cache set() was not expected to be called');
        }
        if (isset($this->setExpectations[$this->setCalls])) {
            ++$this->setCalls;
        }
        $this->storage[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        if (isset($this->deleteExpectations[$this->deleteCalls])) {
            $expectedKey = $this->deleteExpectations[$this->deleteCalls];
            if ($key === $expectedKey) {
                ++$this->deleteCalls;
            }
        }
        unset($this->storage[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->storage = [];

        return true;
    }

    /** @param iterable<string> $keys */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param iterable<string, mixed> $values
     * @phpstan-ignore-next-line
     */
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    /** @param iterable<string> $keys */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string) $key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return isset($this->storage[$key]);
    }
}
