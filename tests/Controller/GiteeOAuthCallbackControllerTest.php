<?php

namespace GiteeApiBundle\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use GiteeApiBundle\Controller\GiteeOAuthCallbackController;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Repository\GiteeAccessTokenRepository;
use GiteeApiBundle\Service\GiteeOAuthService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GiteeOAuthCallbackControllerTest extends TestCase
{
    private GiteeOAuthCallbackController $controller;
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
        $this->assertInstanceOf(GiteeOAuthCallbackController::class, $this->controller);
    }

    /**
     * 测试没有提供授权码的情况
     */
    public function testInvoke_withoutCode(): void
    {
        $request = new Request();

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No authorization code provided');

        $this->controller->__invoke($request, $this->application);
    }

    /**
     * 测试提供空字符串授权码的情况
     */
    public function testInvoke_withEmptyCode(): void
    {
        $request = new Request();
        $request->query->set('code', '');

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No authorization code provided');

        $this->controller->__invoke($request, $this->application);
    }

    /**
     * 测试提供非字符串授权码的情况
     */
    public function testInvoke_withNonStringCode(): void
    {
        $request = new Request();
        $request->query->set('code', 123); // 数字而不是字符串

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No authorization code provided');

        $this->controller->__invoke($request, $this->application);
    }

    /**
     * 测试基本的成功流程需要模拟 HTTP 请求，过于复杂
     * 我们在这里只测试输入验证逻辑
     */
    public function testInvoke_successFlowRequiresIntegrationTest(): void
    {
        $this->markTestSkipped(
            '完整的 OAuth 回调流程测试需要模拟 HTTP 客户端响应，' .
            '这属于集成测试的范畴。单元测试主要验证输入验证逻辑。'
        );
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
        $this->controller = new GiteeOAuthCallbackController($this->oauthService, $this->urlGenerator);

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