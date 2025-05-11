<?php

namespace GiteeApiBundle\Service;

use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Exception\GiteeApiException;
use GiteeApiBundle\Repository\GiteeAccessTokenRepository;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class GiteeApiClient
{
    private const API_BASE_URL = 'https://gitee.com/api/v5';

    private HttpClientInterface $client;

    public function __construct(
        private readonly GiteeAccessTokenRepository $tokenRepository,
    ) {
        $this->client = HttpClient::create();
    }

    public function request(string $method, string $path, array $options = [], ?string $userId = null, ?GiteeApplication $application = null): array
    {
        if ($userId && $application) {
            $token = $this->tokenRepository->findLatestByUserAndApplication($userId, $application->getId());
            if ($token) {
                $options['headers'] = array_merge(
                    $options['headers'] ?? [],
                    ['Authorization' => 'Bearer ' . $token->getAccessToken()]
                );
            }
        }

        try {
            $response = $this->client->request(
                $method,
                self::API_BASE_URL . $path,
                $options
            );

            return $response->toArray();
        } catch (\Exception $e) {
            throw new GiteeApiException(sprintf('API请求失败: %s', $e->getMessage()), 0, $e);
        }
    }

    public function getUser(string $userId, GiteeApplication $application): array
    {
        return $this->request('GET', '/user', [], $userId, $application);
    }

    /**
     * 获取用户的仓库列表
     *
     * @param string $userId 用户ID
     * @param GiteeApplication $application 应用
     * @param array $params 额外的查询参数
     * @return array 仓库列表
     * @throws GiteeApiException
     */
    public function getRepositories(string $userId, GiteeApplication $application, array $params = []): array
    {
        $token = $this->tokenRepository->findLatestByUserAndApplication($userId, $application->getId());
        if (!$token) {
            throw new GiteeApiException('未找到有效的访问令牌');
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
            if (empty($data)) {
                break;
            }

            $repositories = array_merge($repositories, $data);
            $params['page']++;
        } while (count($data) === $params['per_page']);

        return $repositories;
    }

    public function getRepository(string $owner, string $repo, ?string $userId = null, ?GiteeApplication $application = null): array
    {
        return $this->request('GET', "/repos/$owner/$repo", [], $userId, $application);
    }

    public function getBranches(string $owner, string $repo, ?string $userId = null, ?GiteeApplication $application = null): array
    {
        return $this->request('GET', "/repos/$owner/$repo/branches", [], $userId, $application);
    }

    public function getIssues(string $owner, string $repo, array $params = [], ?string $userId = null, ?GiteeApplication $application = null): array
    {
        return $this->request('GET', "/repos/$owner/$repo/issues", [
            'query' => $params,
        ], $userId, $application);
    }

    public function getPullRequests(string $owner, string $repo, array $params = [], ?string $userId = null, ?GiteeApplication $application = null): array
    {
        return $this->request('GET', "/repos/$owner/$repo/pulls", [
            'query' => $params,
        ], $userId, $application);
    }

    /**
     * 发送GET请求
     *
     * @param string $endpoint API端点
     * @param array $params 查询参数
     * @return ResponseInterface
     * @throws GiteeApiException
     */
    private function get(string $endpoint, array $params = []): ResponseInterface
    {
        try {
            $response = $this->client->request('GET', self::API_BASE_URL . $endpoint, [
                'query' => $params,
            ]);

            return $response;
        } catch (\Exception $e) {
            throw new GiteeApiException(sprintf('API请求失败: %s', $e->getMessage()), 0, $e);
        }
    }
}
