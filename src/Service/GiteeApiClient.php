<?php

declare(strict_types=1);

namespace GiteeApiBundle\Service;

use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Exception\GiteeApiException;
use GiteeApiBundle\Repository\GiteeAccessTokenRepository;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[WithMonologChannel(channel: 'gitee_api')]
final class GiteeApiClient implements GiteeApiClientInterface
{
    private const API_BASE_URL = 'https://gitee.com/api/v5';

    private HttpClientInterface $client;

    public function __construct(
        private readonly GiteeAccessTokenRepository $tokenRepository,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        $this->client = HttpClient::create();
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, array $options = [], ?string $userId = null, ?GiteeApplication $application = null): array
    {
        $startTime = microtime(true);
        $url = self::API_BASE_URL . $path;

        if (null !== $userId && null !== $application) {
            $token = $this->tokenRepository->findLatestByUserAndApplication($userId, (string) $application->getId());
            if (null !== $token) {
                $options['headers'] = array_merge(
                    $options['headers'] ?? [],
                    ['Authorization' => 'Bearer ' . $token->getAccessToken()]
                );
            }
        }

        $this->logger->info('Gitee API request started', [
            'method' => $method,
            'url' => $url,
            'userId' => $userId,
            'applicationId' => $application?->getId(),
        ]);

        try {
            $response = $this->client->request(
                $method,
                $url,
                $options
            );

            $data = $response->toArray();
            $duration = microtime(true) - $startTime;

            $this->logger->info('Gitee API request successful', [
                'method' => $method,
                'url' => $url,
                'userId' => $userId,
                'applicationId' => $application?->getId(),
                'statusCode' => $response->getStatusCode(),
                'duration' => round($duration, 3),
                'responseSize' => strlen((string) json_encode($data)),
            ]);

            return $data;
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;

            $this->logger->error('Gitee API request failed', [
                'method' => $method,
                'url' => $url,
                'userId' => $userId,
                'applicationId' => $application?->getId(),
                'duration' => round($duration, 3),
                'error' => $e->getMessage(),
                'errorClass' => get_class($e),
            ]);

            throw GiteeApiException::create(sprintf('API请求失败: %s', $e->getMessage()), 0, $e);
        }
    }

    /** @return array<string, mixed> */
    public function getUser(string $userId, GiteeApplication $application): array
    {
        return $this->request('GET', '/user', [], $userId, $application);
    }

    /**
     * 获取用户的仓库列表
     *
     * @param string           $userId      用户ID
     * @param GiteeApplication $application 应用
     * @param array<string, mixed> $params      额外的查询参数
     *
     * @return array<array-key, array<string, mixed>> 仓库列表
     *
     * @throws GiteeApiException
     */
    public function getRepositories(string $userId, GiteeApplication $application, array $params = []): array
    {
        $token = $this->tokenRepository->findLatestByUserAndApplication($userId, (string) $application->getId());
        if (null === $token) {
            throw GiteeApiException::create('未找到有效的访问令牌');
        }

        $defaultParams = [
            'access_token' => $token->getAccessToken(),
            'sort' => 'pushed',
            'direction' => 'desc',
            'per_page' => 100,
            'page' => 1,
        ];

        $params = array_merge($defaultParams, $params);
        $repositories = [];

        do {
            $response = $this->get('/user/repos', $params);
            $data = $response->toArray();
            if ([] === $data) {
                break;
            }

            $repositories = array_merge($repositories, $data);
            ++$params['page'];
        } while (count($data) === $params['per_page']);

        return $repositories;
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

    /**
     * @param array<string, mixed> $params
     * @return array<array-key, array<string, mixed>>
     */
    public function getIssues(string $owner, string $repo, array $params = [], ?string $userId = null, ?GiteeApplication $application = null): array
    {
        return $this->request('GET', "/repos/{$owner}/{$repo}/issues", [
            'query' => $params,
        ], $userId, $application);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<array-key, array<string, mixed>>
     */
    public function getPullRequests(string $owner, string $repo, array $params = [], ?string $userId = null, ?GiteeApplication $application = null): array
    {
        return $this->request('GET', "/repos/{$owner}/{$repo}/pulls", [
            'query' => $params,
        ], $userId, $application);
    }

    /**
     * 发送GET请求
     *
     * @param string $endpoint API端点
     * @param array<string, mixed> $params   查询参数
     *
     * @throws GiteeApiException
     */
    private function get(string $endpoint, array $params = []): ResponseInterface
    {
        $startTime = microtime(true);
        $url = self::API_BASE_URL . $endpoint;

        $this->logger->info('Gitee API GET request started', [
            'url' => $url,
            'params' => $params,
        ]);

        try {
            $response = $this->client->request('GET', $url, [
                'query' => $params,
            ]);

            $duration = microtime(true) - $startTime;

            $this->logger->info('Gitee API GET request successful', [
                'url' => $url,
                'params' => $params,
                'statusCode' => $response->getStatusCode(),
                'duration' => round($duration, 3),
            ]);

            return $response;
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;

            $this->logger->error('Gitee API GET request failed', [
                'url' => $url,
                'params' => $params,
                'duration' => round($duration, 3),
                'error' => $e->getMessage(),
                'errorClass' => get_class($e),
            ]);

            throw GiteeApiException::create(sprintf('API请求失败: %s', $e->getMessage()), 0, $e);
        }
    }
}
