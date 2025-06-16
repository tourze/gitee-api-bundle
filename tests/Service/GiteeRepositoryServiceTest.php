<?php

namespace GiteeApiBundle\Tests\Service;

use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Repository\GiteeAccessTokenRepository;
use GiteeApiBundle\Service\GiteeApiClient;
use GiteeApiBundle\Service\GiteeRepositoryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GiteeRepositoryServiceTest extends TestCase
{
    private GiteeRepositoryService $repositoryService;
    private MockObject $httpClient;
    private MockObject $tokenRepository;
    private GiteeApplication $application;

    protected function setUp(): void
    {
        // 创建依赖的 mock
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->tokenRepository = $this->createMock(GiteeAccessTokenRepository::class);

        // 创建真实的 GiteeApiClient 实例
        $giteeApiClient = new GiteeApiClient($this->tokenRepository);

        // 使用反射替换内部的 HttpClient
        $reflectionProperty = new \ReflectionProperty(GiteeApiClient::class, 'client');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($giteeApiClient, $this->httpClient);

        // 创建 GiteeRepositoryService 实例
        $this->repositoryService = new GiteeRepositoryService($giteeApiClient);

        // 创建应用实例
        $this->application = new GiteeApplication();
        $this->application->setName('Test App')
            ->setClientId('client_id')
            ->setClientSecret('client_secret');

        // 使用反射设置 ID
        $reflectionProperty = new \ReflectionProperty(GiteeApplication::class, 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->application, 1);
    }

    /**
     * 测试获取仓库列表
     */
    public function testGetRepositories(): void
    {
        $userId = 'testuser';
        $expectedResult = [
            ['id' => 1, 'name' => 'repo1'],
            ['id' => 2, 'name' => 'repo2'],
        ];

        // 模拟 token repository 返回 null（没有 token）
        $this->tokenRepository->expects($this->once())
            ->method('findLatestByUserAndApplication')
            ->with($userId, $this->application->getId())
            ->willReturn(null);

        // 模拟 HTTP 响应
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn($expectedResult);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://gitee.com/api/v5/user/repos',
                [
                    'query' => [
                        'sort' => 'pushed',
                        'direction' => 'desc',
                        'per_page' => 100,
                        'page' => 1,
                    ]
                ]
            )
            ->willReturn($response);

        $result = $this->repositoryService->getRepositories($userId, $this->application);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * 测试获取单个仓库
     */
    public function testGetRepository(): void
    {
        $owner = 'testowner';
        $repo = 'testrepo';
        $userId = 'testuser';
        $expectedResult = ['id' => 1, 'name' => 'testrepo', 'owner' => 'testowner'];

        // 模拟 token repository 返回 null（没有 token）
        $this->tokenRepository->expects($this->once())
            ->method('findLatestByUserAndApplication')
            ->with($userId, $this->application->getId())
            ->willReturn(null);

        // 模拟 HTTP 响应
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn($expectedResult);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                "https://gitee.com/api/v5/repos/$owner/$repo",
                []
            )
            ->willReturn($response);

        $result = $this->repositoryService->getRepository($owner, $repo, $userId, $this->application);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * 测试获取分支列表
     */
    public function testGetBranches(): void
    {
        $owner = 'testowner';
        $repo = 'testrepo';
        $userId = 'testuser';
        $expectedResult = [
            ['name' => 'master'],
            ['name' => 'develop'],
        ];

        // 模拟 token repository 返回 null（没有 token）
        $this->tokenRepository->expects($this->once())
            ->method('findLatestByUserAndApplication')
            ->with($userId, $this->application->getId())
            ->willReturn(null);

        // 模拟 HTTP 响应
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn($expectedResult);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                "https://gitee.com/api/v5/repos/$owner/$repo/branches",
                []
            )
            ->willReturn($response);

        $result = $this->repositoryService->getBranches($owner, $repo, $userId, $this->application);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * 测试获取Issue列表
     */
    public function testGetIssues(): void
    {
        $owner = 'testowner';
        $repo = 'testrepo';
        $userId = 'testuser';
        $params = ['state' => 'open'];
        $expectedResult = [
            ['id' => 1, 'title' => 'Issue 1'],
            ['id' => 2, 'title' => 'Issue 2'],
        ];

        // 模拟 token repository 返回 null（没有 token）
        $this->tokenRepository->expects($this->once())
            ->method('findLatestByUserAndApplication')
            ->with($userId, $this->application->getId())
            ->willReturn(null);

        // 模拟 HTTP 响应
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn($expectedResult);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                "https://gitee.com/api/v5/repos/$owner/$repo/issues",
                ['query' => $params]
            )
            ->willReturn($response);

        $result = $this->repositoryService->getIssues($owner, $repo, $params, $userId, $this->application);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * 测试获取Pull Request列表
     */
    public function testGetPullRequests(): void
    {
        $owner = 'testowner';
        $repo = 'testrepo';
        $userId = 'testuser';
        $params = ['state' => 'open'];
        $expectedResult = [
            ['id' => 1, 'title' => 'PR 1'],
            ['id' => 2, 'title' => 'PR 2'],
        ];

        // 模拟 token repository 返回 null（没有 token）
        $this->tokenRepository->expects($this->once())
            ->method('findLatestByUserAndApplication')
            ->with($userId, $this->application->getId())
            ->willReturn(null);

        // 模拟 HTTP 响应
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn($expectedResult);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                "https://gitee.com/api/v5/repos/$owner/$repo/pulls",
                ['query' => $params]
            )
            ->willReturn($response);

        $result = $this->repositoryService->getPullRequests($owner, $repo, $params, $userId, $this->application);

        $this->assertEquals($expectedResult, $result);
    }
}