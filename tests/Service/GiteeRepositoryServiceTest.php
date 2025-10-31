<?php

declare(strict_types=1);

namespace GiteeApiBundle\Tests\Service;

use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Service\GiteeApiClientInterface;
use GiteeApiBundle\Service\GiteeRepositoryService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(GiteeRepositoryService::class)]
#[RunTestsInSeparateProcesses]
final class GiteeRepositoryServiceTest extends AbstractIntegrationTestCase
{
    private GiteeRepositoryService $repositoryService;

    private MockApiClient $giteeApiClient;

    private GiteeApplication $application;

    protected function onSetUp(): void
    {
        // 创建 GiteeApiClientInterface 的匿名类实现
        $this->giteeApiClient = $this->createMockApiClient();

        // 将对象注册到容器中
        $container = self::getContainer();
        $container->set(GiteeApiClientInterface::class, $this->giteeApiClient);

        // 从容器获取 GiteeRepositoryService
        $this->repositoryService = self::getService(GiteeRepositoryService::class);

        // 创建应用实例
        $this->application = new GiteeApplication();
        $this->application->setName('Test App');
        $this->application->setClientId('client_id');
        $this->application->setClientSecret('client_secret');

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

        // 设置 GiteeApiClient 的期望请求
        $this->giteeApiClient->expectRequest(
            'GET',
            '/user/repos',
            [
                'query' => [
                    'sort' => 'pushed',
                    'direction' => 'desc',
                    'per_page' => 100,
                    'page' => 1,
                ],
            ],
            $userId,
            $this->application,
            $expectedResult
        );

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

        // 设置 GiteeApiClient 的期望请求
        $this->giteeApiClient->expectRequest(
            'GET',
            "/repos/{$owner}/{$repo}",
            [],
            $userId,
            $this->application,
            $expectedResult
        );

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

        // 设置 GiteeApiClient 的期望请求
        $this->giteeApiClient->expectRequest(
            'GET',
            "/repos/{$owner}/{$repo}/branches",
            [],
            $userId,
            $this->application,
            $expectedResult
        );

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

        // 设置 GiteeApiClient 的期望请求
        $this->giteeApiClient->expectRequest(
            'GET',
            "/repos/{$owner}/{$repo}/issues",
            ['query' => $params],
            $userId,
            $this->application,
            $expectedResult
        );

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

        // 设置 GiteeApiClient 的期望请求
        $this->giteeApiClient->expectRequest(
            'GET',
            "/repos/{$owner}/{$repo}/pulls",
            ['query' => $params],
            $userId,
            $this->application,
            $expectedResult
        );

        $result = $this->repositoryService->getPullRequests($owner, $repo, $params, $userId, $this->application);

        $this->assertEquals($expectedResult, $result);
    }

    private function createMockApiClient(): MockApiClient
    {
        return new MockApiClient();
    }
}
