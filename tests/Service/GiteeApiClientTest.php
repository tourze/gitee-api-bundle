<?php

namespace GiteeApiBundle\Tests\Service;

use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Exception\GiteeApiException;
use GiteeApiBundle\Service\GiteeApiClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(GiteeApiClient::class)]
#[RunTestsInSeparateProcesses]
final class GiteeApiClientTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 从容器中获取服务
        $this->apiClient = self::getService(GiteeApiClient::class);
    }

    private GiteeApiClient $apiClient;

    /**
     * 测试API客户端基本功能
     */
    public function testApiClientCanBeInstantiated(): void
    {
        $this->assertInstanceOf(GiteeApiClient::class, $this->apiClient);
    }

    /**
     * 测试不带Token的请求会抛出异常
     */
    public function testRequestWithoutTokenAndUserThrowsException(): void
    {
        $this->expectException(GiteeApiException::class);

        // 创建一个测试应用
        $application = new GiteeApplication();
        $application->setName('Test App');
        $application->setClientId('client_id');
        $application->setClientSecret('client_secret');

        // 尝试获取需要认证的API
        $this->apiClient->getUser('nonexistent-user', $application);
    }
}
