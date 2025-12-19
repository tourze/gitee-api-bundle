<?php

declare(strict_types=1);

namespace GiteeApiBundle\Tests\Service;

use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Enum\GiteeScope;
use GiteeApiBundle\Service\GiteeOAuthService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * GiteeOAuthService 集成测试
 *
 * 测试服务的初始化、依赖注入和基本功能验证
 * 不进行实际的外部 HTTP 调用
 *
 * @internal
 */
#[CoversClass(GiteeOAuthService::class)]
#[RunTestsInSeparateProcesses]
final class GiteeOAuthServiceTest extends AbstractIntegrationTestCase
{
    private GiteeOAuthService $oauthService;

    private GiteeApplication $application;

    protected function onSetUp(): void
    {
        // 从容器中获取服务 - 验证依赖注入正确
        $this->oauthService = self::getService(GiteeOAuthService::class);

        // 创建测试应用
        $this->application = new GiteeApplication();
        $this->application->setName('Test App');
        $this->application->setClientId('test_client_id');
        $this->application->setClientSecret('test_client_secret');
        $this->application->setScopes([GiteeScope::USER, GiteeScope::PROJECTS]);

        // 持久化到数据库
        self::getEntityManager()->persist($this->application);
        self::getEntityManager()->flush();
    }

    /**
     * 测试服务可以从容器获取
     */
    public function testServiceCanBeRetrievedFromContainer(): void
    {
        $this->assertInstanceOf(GiteeOAuthService::class, $this->oauthService);
    }

    /**
     * 测试生成授权URL（不带回调URL）
     */
    public function testGetAuthorizationUrlWithoutCallbackUrl(): void
    {
        $redirectUri = 'https://example.com/callback';

        $url = $this->oauthService->getAuthorizationUrl($this->application, $redirectUri);

        // 验证URL包含正确的参数
        $this->assertStringContainsString('client_id=test_client_id', $url);
        $this->assertStringContainsString('redirect_uri=' . urlencode($redirectUri), $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('scope=user_info+projects', $url);
        $this->assertStringContainsString('state=', $url);
        $this->assertStringContainsString('gitee.com/oauth/authorize', $url);
    }

    /**
     * 测试生成授权URL（带回调URL）
     */
    public function testGetAuthorizationUrlWithCallbackUrl(): void
    {
        $redirectUri = 'https://example.com/callback';
        $callbackUrl = 'https://example.com/custom_callback';

        $url = $this->oauthService->getAuthorizationUrl($this->application, $redirectUri, $callbackUrl);

        // 验证URL包含正确的参数
        $this->assertStringContainsString('client_id=test_client_id', $url);
        $this->assertStringContainsString('redirect_uri=' . urlencode($redirectUri), $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('scope=user_info+projects', $url);
        $this->assertStringContainsString('state=', $url);
    }

    /**
     * 测试验证无效的state
     */
    public function testVerifyStateWithInvalidState(): void
    {
        $state = 'invalid_state_that_was_never_set';

        $result = $this->oauthService->verifyState($state);

        $this->assertNull($result);
    }

    /**
     * 测试获取访问令牌（用户没有Token）
     */
    public function testGetAccessTokenWithNoToken(): void
    {
        $userId = 'nonexistent_user';

        $result = $this->oauthService->getAccessToken($userId, $this->application);

        $this->assertNull($result);
    }

    /**
     * 测试授权URL每次生成的state都不同
     */
    public function testAuthorizationUrlGeneratesUniqueState(): void
    {
        $redirectUri = 'https://example.com/callback';

        $url1 = $this->oauthService->getAuthorizationUrl($this->application, $redirectUri);
        $url2 = $this->oauthService->getAuthorizationUrl($this->application, $redirectUri);

        // 提取两个URL中的state参数
        preg_match('/state=([^&]+)/', $url1, $matches1);
        preg_match('/state=([^&]+)/', $url2, $matches2);

        $this->assertNotEmpty($matches1[1]);
        $this->assertNotEmpty($matches2[1]);
        $this->assertNotEquals($matches1[1], $matches2[1], 'Each call should generate a unique state');
    }

    /**
     * 测试state长度符合预期（32字符的十六进制字符串）
     */
    public function testStateHasExpectedLength(): void
    {
        $redirectUri = 'https://example.com/callback';

        $url = $this->oauthService->getAuthorizationUrl($this->application, $redirectUri);

        preg_match('/state=([^&]+)/', $url, $matches);
        $state = $matches[1];

        // bin2hex(random_bytes(16)) 生成32字符
        $this->assertEquals(32, strlen($state));
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $state);
    }

    /**
     * 测试 handleCallback 方法签名
     * 验证方法存在且参数符合预期
     */
    public function testHandleCallbackMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(GiteeOAuthService::class, 'handleCallback');
        $parameters = $reflection->getParameters();

        $this->assertTrue($reflection->isPublic());
        $this->assertCount(3, $parameters);
        $this->assertEquals('code', $parameters[0]->getName());
        $this->assertEquals('application', $parameters[1]->getName());
        $this->assertEquals('redirectUri', $parameters[2]->getName());

        // 验证返回类型
        $returnType = $reflection->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertEquals('GiteeApiBundle\Entity\GiteeAccessToken', $returnType->getName());
    }

    /**
     * 测试 refreshToken 方法签名
     * 验证方法存在且参数符合预期
     */
    public function testRefreshTokenMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(GiteeOAuthService::class, 'refreshToken');
        $parameters = $reflection->getParameters();

        $this->assertTrue($reflection->isPublic());
        $this->assertCount(1, $parameters);
        $this->assertEquals('token', $parameters[0]->getName());

        // 验证参数类型
        $paramType = $parameters[0]->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $paramType);
        $this->assertEquals('GiteeApiBundle\Entity\GiteeAccessToken', $paramType->getName());

        // 验证返回类型
        $returnType = $reflection->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertEquals('GiteeApiBundle\Entity\GiteeAccessToken', $returnType->getName());
    }
}
