<?php

namespace GiteeApiBundle\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use GiteeApiBundle\Controller\GiteeOAuthConnectController;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Repository\GiteeAccessTokenRepository;
use GiteeApiBundle\Service\GiteeOAuthService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GiteeOAuthConnectControllerTest extends TestCase
{
    private GiteeOAuthConnectController $controller;
    private GiteeOAuthService $oauthService;
    private MockObject $urlGenerator;
    private MockObject $httpClient;
    private MockObject $entityManager;
    private MockObject $tokenRepository;
    private MockObject $cache;
    private GiteeApplication $application;

    /**
     * 测试构造函数
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(GiteeOAuthConnectController::class, $this->controller);
    }

    /**
     * 测试连接方法的基本功能
     */
    public function testInvoke_basicFunctionality(): void
    {
        $request = new Request();
        $callbackUrl = 'https://example.com/custom-callback';
        $request->query->set('callbackUrl', $callbackUrl);

        $redirectUrl = 'https://gitee.com/oauth/callback';

        // 配置 URL 生成器
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with(
                'gitee_oauth_callback',
                ['applicationId' => 1],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($redirectUrl);

        // 配置缓存
        $this->cache->expects($this->once())
            ->method('set')
            ->with(
                $this->matchesRegularExpression('/^gitee_oauth_state_[a-f0-9]{32}$/'),
                $callbackUrl,
                3600
            );

        $response = $this->controller->__invoke($request, $this->application);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $targetUrl = $response->getTargetUrl();
        $this->assertStringContainsString('https://gitee.com/oauth/authorize', $targetUrl);
        $this->assertStringContainsString('client_id=client_id', $targetUrl);
        $this->assertStringContainsString('redirect_uri=' . urlencode($redirectUrl), $targetUrl);
    }

    /**
     * 测试连接方法，没有提供回调URL的情况
     */
    public function testInvoke_withoutCallbackUrl(): void
    {
        $request = new Request();

        $redirectUrl = 'https://gitee.com/oauth/callback';

        // 配置 URL 生成器
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with(
                'gitee_oauth_callback',
                ['applicationId' => 1],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($redirectUrl);

        // 配置缓存 - 没有回调URL时不应该调用 set
        $this->cache->expects($this->never())
            ->method('set');

        $response = $this->controller->__invoke($request, $this->application);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $targetUrl = $response->getTargetUrl();
        $this->assertStringContainsString('https://gitee.com/oauth/authorize', $targetUrl);
        $this->assertStringContainsString('client_id=client_id', $targetUrl);
        $this->assertStringContainsString('redirect_uri=' . urlencode($redirectUrl), $targetUrl);
    }

    /**
     * 测试连接方法，提供空字符串回调URL的情况
     */
    public function testInvoke_withEmptyCallbackUrl(): void
    {
        $request = new Request();
        $request->query->set('callbackUrl', ''); // 空字符串

        $redirectUrl = 'https://gitee.com/oauth/callback';

        // 配置 URL 生成器
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with(
                'gitee_oauth_callback',
                ['applicationId' => 1],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($redirectUrl);

        // 配置缓存 - 空字符串会被当作有效的回调URL
        $this->cache->expects($this->once())
            ->method('set')
            ->with(
                $this->matchesRegularExpression('/^gitee_oauth_state_[a-f0-9]{32}$/'),
                '',
                3600
            );

        $response = $this->controller->__invoke($request, $this->application);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $targetUrl = $response->getTargetUrl();
        $this->assertStringContainsString('https://gitee.com/oauth/authorize', $targetUrl);
        $this->assertStringContainsString('client_id=client_id', $targetUrl);
        $this->assertStringContainsString('redirect_uri=' . urlencode($redirectUrl), $targetUrl);
    }

    protected function setUp(): void
    {
        // 创建依赖的 mock
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->tokenRepository = $this->createMock(GiteeAccessTokenRepository::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        // 创建真实的 GiteeOAuthService
        $this->oauthService = new GiteeOAuthService(
            $this->httpClient,
            $this->entityManager,
            $this->tokenRepository,
            $this->cache
        );

        // 创建控制器实例
        $this->controller = new GiteeOAuthConnectController($this->oauthService, $this->urlGenerator);

        // 创建应用实例
        $this->application = new GiteeApplication();
        $this->application->setName('Test App')
            ->setClientId('client_id')
            ->setClientSecret('client_secret')
            ->setScopes([]);

        // 使用反射设置 ID
        $reflectionProperty = new \ReflectionProperty(GiteeApplication::class, 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->application, 1);
    }
}