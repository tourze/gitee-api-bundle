<?php

declare(strict_types=1);

namespace GiteeApiBundle\Tests\Service;

use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Service\GiteeApiClientInterface;

/**
 * @internal
 */
final class MockApiClient implements GiteeApiClientInterface
{
    /** @var array<int, array{method: string, endpoint: string, options: array<string, mixed>, userId: string, application: GiteeApplication, returnValue: mixed}> */
    private array $requestExpectations = [];

    private int $callCount = 0;

    /** @param array<string, mixed> $options */
    public function expectRequest(string $method, string $endpoint, array $options, string $userId, GiteeApplication $application, mixed $returnValue): void
    {
        $this->requestExpectations[] = [
            'method' => $method,
            'endpoint' => $endpoint,
            'options' => $options,
            'userId' => $userId,
            'application' => $application,
            'returnValue' => $returnValue,
        ];
    }

    /** @param array<string, mixed> $options */
    public function request(string $method, string $endpoint, array $options = [], ?string $userId = null, ?GiteeApplication $application = null): array
    {
        if (!isset($this->requestExpectations[$this->callCount])) {
            throw new \RuntimeException('Unexpected request: ' . $method . ' ' . $endpoint);
        }

        $expectation = $this->requestExpectations[$this->callCount];
        ++$this->callCount;

        // 验证请求参数
        if ($expectation['method'] !== $method) {
            throw new \RuntimeException("Expected method {$expectation['method']}, got {$method}");
        }
        if ($expectation['endpoint'] !== $endpoint) {
            throw new \RuntimeException("Expected endpoint {$expectation['endpoint']}, got {$endpoint}");
        }
        if ($expectation['options'] !== $options) {
            throw new \RuntimeException('Expected options do not match');
        }
        if ($expectation['userId'] !== $userId) {
            throw new \RuntimeException("Expected userId {$expectation['userId']}, got {$userId}");
        }
        if ($expectation['application'] !== $application) {
            throw new \RuntimeException('Expected application does not match');
        }

        return $expectation['returnValue'];
    }

    /** @return array<string, mixed> */
    public function getUser(string $userId, GiteeApplication $application): array
    {
        return $this->request('GET', '/user', [], $userId, $application);
    }

    /** @param array<string, mixed> $params */
    public function getRepositories(string $userId, GiteeApplication $application, array $params = []): array
    {
        return $this->request('GET', '/user/repos', ['query' => $params], $userId, $application);
    }

    /** @return array<string, mixed> */
    public function getRepository(string $owner, string $repo, ?string $userId = null, ?GiteeApplication $application = null): array
    {
        return $this->request('GET', "/repos/{$owner}/{$repo}", [], $userId, $application);
    }

    /** @return array<array-key, array<string, mixed>> */
    public function getBranches(string $owner, string $repo, ?string $userId = null, ?GiteeApplication $application = null): array
    {
        return $this->request('GET', "/repos/{$owner}/{$repo}/branches", [], $userId, $application);
    }

    /** @param array<string, mixed> $params */
    public function getIssues(string $owner, string $repo, array $params = [], ?string $userId = null, ?GiteeApplication $application = null): array
    {
        return $this->request('GET', "/repos/{$owner}/{$repo}/issues", ['query' => $params], $userId, $application);
    }

    /** @param array<string, mixed> $params */
    public function getPullRequests(string $owner, string $repo, array $params = [], ?string $userId = null, ?GiteeApplication $application = null): array
    {
        return $this->request('GET', "/repos/{$owner}/{$repo}/pulls", ['query' => $params], $userId, $application);
    }
}
