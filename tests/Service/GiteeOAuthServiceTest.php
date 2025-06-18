<?php

namespace GiteeApiBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use GiteeApiBundle\Entity\GiteeAccessToken;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Enum\GiteeScope;
use GiteeApiBundle\Repository\GiteeAccessTokenRepository;
use GiteeApiBundle\Service\GiteeOAuthService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GiteeOAuthServiceTest extends TestCase
{
    private GiteeOAuthService $oauthService;
    private MockObject $httpClient;
    private MockObject $entityManager;
    private MockObject $tokenRepository;
    private MockObject $cache;
    private GiteeApplication $application;
    
    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->tokenRepository = $this->createMock(GiteeAccessTokenRepository::class);
        $this->cache = $this->createMock(CacheInterface::class);
        
        $this->oauthService = new GiteeOAuthService(
            $this->httpClient,
            $this->entityManager,
            $this->tokenRepository,
            $this->cache
        );
        
        $this->application = new GiteeApplication();
        $this->application->setName('Test App')
            ->setClientId('client_id')
            ->setClientSecret('client_secret')
            ->setScopes([GiteeScope::USER, GiteeScope::PROJECTS]);
    }
    
    /**
     * 测试生成授权URL（不带回调URL）
     */
    public function testGetAuthorizationUrl_withoutCallbackUrl(): void
    {
        $redirectUri = 'https://example.com/callback';
        
        // 不应该调用缓存存储
        $this->cache->expects($this->never())
            ->method('set');
            
        // 测试授权URL
        $url = $this->oauthService->getAuthorizationUrl($this->application, $redirectUri);
        
        // 验证URL包含正确的参数
        $this->assertStringContainsString('client_id=client_id', $url);
        $this->assertStringContainsString('redirect_uri=' . urlencode($redirectUri), $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('scope=user_info+projects', $url);
        $this->assertStringContainsString('state=', $url);
    }
    
    /**
     * 测试生成授权URL（带回调URL）
     */
    public function testGetAuthorizationUrl_withCallbackUrl(): void
    {
        $redirectUri = 'https://example.com/callback';
        $callbackUrl = 'https://example.com/custom_callback';
        
        // 应该调用缓存存储
        $this->cache->expects($this->once())
            ->method('set')
            ->with(
                $this->matchesRegularExpression('/^gitee_oauth_state_[a-f0-9]{32}$/'),
                $callbackUrl,
                3600 // TTL
            );
            
        // 测试授权URL
        $url = $this->oauthService->getAuthorizationUrl($this->application, $redirectUri, $callbackUrl);
        
        // 验证URL包含正确的参数
        $this->assertStringContainsString('client_id=client_id', $url);
        $this->assertStringContainsString('redirect_uri=' . urlencode($redirectUri), $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('scope=user_info+projects', $url);
        $this->assertStringContainsString('state=', $url);
    }
    
    /**
     * 测试验证有效的state
     */
    public function testVerifyState_withValidState(): void
    {
        $state = 'valid_state';
        $callbackUrl = 'https://example.com/callback';
        
        $this->cache->expects($this->once())
            ->method('get')
            ->with("gitee_oauth_state_{$state}")
            ->willReturn($callbackUrl);
            
        $this->cache->expects($this->once())
            ->method('delete')
            ->with("gitee_oauth_state_{$state}");
            
        $result = $this->oauthService->verifyState($state);
        
        $this->assertEquals($callbackUrl, $result);
    }
    
    /**
     * 测试验证无效的state
     */
    public function testVerifyState_withInvalidState(): void
    {
        $state = 'invalid_state';
        
        $this->cache->expects($this->once())
            ->method('get')
            ->with("gitee_oauth_state_{$state}")
            ->willReturn(null);
            
        $this->cache->expects($this->once())
            ->method('delete')
            ->with("gitee_oauth_state_{$state}");
            
        $result = $this->oauthService->verifyState($state);
        
        $this->assertNull($result);
    }
    
    /**
     * 测试处理OAuth回调
     */
    public function testHandleCallback(): void
    {
        $code = 'auth_code';
        $redirectUri = 'https://example.com/callback';
        
        // 准备响应数据
        $tokenData = [
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in' => 7200
        ];
        
        $userData = [
            'login' => 'gitee_user',
            'name' => 'Gitee User'
        ];
        
        // 配置HTTP客户端Mock
        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('toArray')->willReturn($tokenData);
        
        $userResponse = $this->createMock(ResponseInterface::class);
        $userResponse->method('toArray')->willReturn($userData);
        
        $this->httpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function($method, $url, $options) use ($tokenResponse, $userResponse) {
                static $callCount = 0;
                $callCount++;
                
                if ($callCount === 1) {
                    $this->assertEquals('POST', $method);
                    $this->assertEquals('https://gitee.com/oauth/token', $url);
                    return $tokenResponse;
                } else {
                    $this->assertEquals('GET', $method);
                    $this->assertEquals('https://gitee.com/api/v5/user', $url);
                    return $userResponse;
                }
            });
        
        // 配置EntityManager Mock
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function($token) {
                return $token instanceof GiteeAccessToken 
                    && $token->getAccessToken() === 'new_access_token'
                    && $token->getRefreshToken() === 'new_refresh_token'
                    && $token->getGiteeUsername() === 'gitee_user';
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // 执行测试
        $token = $this->oauthService->handleCallback($code, $this->application, $redirectUri);
        
        // 验证结果
        $this->assertInstanceOf(GiteeAccessToken::class, $token);
        $this->assertEquals('new_access_token', $token->getAccessToken());
        $this->assertEquals('new_refresh_token', $token->getRefreshToken());
        $this->assertEquals('gitee_user', $token->getGiteeUsername());
        $this->assertSame($this->application, $token->getApplication());
    }
    
    /**
     * 测试刷新Token
     */
    public function testRefreshToken(): void
    {
        $oldToken = new GiteeAccessToken();
        $oldToken->setRefreshToken('old_refresh_token')
            ->setAccessToken('old_access_token')
            ->setUserId('test_user')
            ->setGiteeUsername('gitee_user')
            ->setApplication($this->application);
        
        // 准备响应数据
        $tokenData = [
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in' => 7200
        ];
        
        // 配置HTTP客户端Mock
        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('toArray')->willReturn($tokenData);
        
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://gitee.com/oauth/token',
                [
                    'body' => [
                        'grant_type' => 'refresh_token',
                        'refresh_token' => 'old_refresh_token',
                        'client_id' => 'client_id',
                        'client_secret' => 'client_secret',
                    ]
                ]
            )
            ->willReturn($tokenResponse);
        
        // 配置EntityManager Mock
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(GiteeAccessToken::class));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        // 执行测试
        $newToken = $this->oauthService->refreshToken($oldToken);
        
        // 验证结果
        $this->assertInstanceOf(GiteeAccessToken::class, $newToken);
        $this->assertEquals('new_access_token', $newToken->getAccessToken());
        $this->assertEquals('new_refresh_token', $newToken->getRefreshToken());
        $this->assertInstanceOf(\DateTimeImmutable::class, $newToken->getExpiresAt());
    }
    
    /**
     * 测试获取访问令牌（未过期）
     */
    public function testGetAccessToken_withValidToken(): void
    {
        $userId = 'test_user';
        $token = new GiteeAccessToken();
        $token->setAccessToken('valid_token')
            ->setExpiresAt(new \DateTimeImmutable('+1 hour'))
            ->setUserId($userId)
            ->setGiteeUsername('gitee_user')
            ->setApplication($this->application);
        
        $this->tokenRepository->expects($this->once())
            ->method('findBy')
            ->with(
                ['userId' => $userId, 'application' => $this->application],
                ['createdAt' => 'DESC']
            )
            ->willReturn([$token]);
        
        // 不应该调用刷新Token
        $this->httpClient->expects($this->never())
            ->method('request');
        
        $result = $this->oauthService->getAccessToken($userId, $this->application);
        
        $this->assertSame($token, $result);
        $this->assertEquals('valid_token', $result->getAccessToken());
    }
    
    /**
     * 测试获取访问令牌（用户没有Token）
     */
    public function testGetAccessToken_withNoToken(): void
    {
        $userId = 'test_user';
        
        $this->tokenRepository->expects($this->once())
            ->method('findBy')
            ->with([
                'userId' => $userId,
                'application' => $this->application
            ])
            ->willReturn([]);
        
        $result = $this->oauthService->getAccessToken($userId, $this->application);
        
        $this->assertNull($result);
    }
} 