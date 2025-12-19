<?php

declare(strict_types=1);

namespace GiteeApiBundle\Tests\Service;

use GiteeApiBundle\Service\GiteeRepositoryService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * GiteeRepositoryService 集成测试
 *
 * 测试服务的初始化和依赖注入
 * 不进行实际的外部 HTTP 调用
 *
 * @internal
 */
#[CoversClass(GiteeRepositoryService::class)]
#[RunTestsInSeparateProcesses]
final class GiteeRepositoryServiceTest extends AbstractIntegrationTestCase
{
    private GiteeRepositoryService $repositoryService;

    protected function onSetUp(): void
    {
        // 从容器获取 GiteeRepositoryService - 验证依赖注入正确
        $this->repositoryService = self::getService(GiteeRepositoryService::class);
    }

    /**
     * 测试服务可以从容器获取
     */
    public function testServiceCanBeRetrievedFromContainer(): void
    {
        $this->assertInstanceOf(GiteeRepositoryService::class, $this->repositoryService);
    }

    /**
     * 测试服务实现了所有预期的公共方法
     */
    public function testServiceHasExpectedMethods(): void
    {
        $reflection = new \ReflectionClass(GiteeRepositoryService::class);

        // 验证预期的公共方法存在
        $this->assertTrue($reflection->hasMethod('getRepositories'));
        $this->assertTrue($reflection->hasMethod('getRepository'));
        $this->assertTrue($reflection->hasMethod('getBranches'));
        $this->assertTrue($reflection->hasMethod('getIssues'));
        $this->assertTrue($reflection->hasMethod('getPullRequests'));

        // 验证方法是公共的
        $this->assertTrue($reflection->getMethod('getRepositories')->isPublic());
        $this->assertTrue($reflection->getMethod('getRepository')->isPublic());
        $this->assertTrue($reflection->getMethod('getBranches')->isPublic());
        $this->assertTrue($reflection->getMethod('getIssues')->isPublic());
        $this->assertTrue($reflection->getMethod('getPullRequests')->isPublic());
    }

    /**
     * 测试 getRepositories 方法签名
     */
    public function testGetRepositoriesMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(GiteeRepositoryService::class, 'getRepositories');
        $parameters = $reflection->getParameters();

        $this->assertCount(3, $parameters);
        $this->assertEquals('userId', $parameters[0]->getName());
        $this->assertEquals('application', $parameters[1]->getName());
        $this->assertEquals('params', $parameters[2]->getName());
        $this->assertTrue($parameters[2]->isOptional());
    }

    /**
     * 测试 getRepository 方法签名
     */
    public function testGetRepositoryMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(GiteeRepositoryService::class, 'getRepository');
        $parameters = $reflection->getParameters();

        $this->assertCount(4, $parameters);
        $this->assertEquals('owner', $parameters[0]->getName());
        $this->assertEquals('repo', $parameters[1]->getName());
        $this->assertEquals('userId', $parameters[2]->getName());
        $this->assertEquals('application', $parameters[3]->getName());
        $this->assertTrue($parameters[2]->allowsNull());
        $this->assertTrue($parameters[3]->allowsNull());
    }

    /**
     * 测试 getBranches 方法签名
     */
    public function testGetBranchesMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(GiteeRepositoryService::class, 'getBranches');
        $parameters = $reflection->getParameters();

        $this->assertCount(4, $parameters);
        $this->assertEquals('owner', $parameters[0]->getName());
        $this->assertEquals('repo', $parameters[1]->getName());
        $this->assertEquals('userId', $parameters[2]->getName());
        $this->assertEquals('application', $parameters[3]->getName());
    }

    /**
     * 测试 getIssues 方法签名
     */
    public function testGetIssuesMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(GiteeRepositoryService::class, 'getIssues');
        $parameters = $reflection->getParameters();

        $this->assertCount(5, $parameters);
        $this->assertEquals('owner', $parameters[0]->getName());
        $this->assertEquals('repo', $parameters[1]->getName());
        $this->assertEquals('params', $parameters[2]->getName());
        $this->assertEquals('userId', $parameters[3]->getName());
        $this->assertEquals('application', $parameters[4]->getName());
    }

    /**
     * 测试 getPullRequests 方法签名
     */
    public function testGetPullRequestsMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(GiteeRepositoryService::class, 'getPullRequests');
        $parameters = $reflection->getParameters();

        $this->assertCount(5, $parameters);
        $this->assertEquals('owner', $parameters[0]->getName());
        $this->assertEquals('repo', $parameters[1]->getName());
        $this->assertEquals('params', $parameters[2]->getName());
        $this->assertEquals('userId', $parameters[3]->getName());
        $this->assertEquals('application', $parameters[4]->getName());
    }
}
