<?php

namespace GiteeApiBundle\Tests\Service;

use GiteeApiBundle\Entity\GiteeAccessToken;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Exception\GiteeApiException;
use GiteeApiBundle\Repository\GiteeAccessTokenRepository;
use GiteeApiBundle\Service\GiteeApiClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GiteeApiClientTest extends TestCase
{
    private GiteeApiClient $apiClient;
    private MockObject $tokenRepository;
    private MockObject $httpClient;
    private GiteeApplication $application;
    private string $userId = 'testuser';
    
    protected function setUp(): void
    {
        $this->tokenRepository = $this->createMock(GiteeAccessTokenRepository::class);
        
        // 创建原始GiteeApiClient，但替换其内部的HttpClient
        $this->apiClient = new GiteeApiClient($this->tokenRepository);
        
        // 使用反射替换内部的client属性
        $reflectionProperty = new \ReflectionProperty(GiteeApiClient::class, 'client');
        $reflectionProperty->setAccessible(true);
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $reflectionProperty->setValue($this->apiClient, $this->httpClient);
        
        // 创建应用实例
        $this->application = new GiteeApplication();
        $this->application->setName('Test App')
            ->setClientId('client_id')
            ->setClientSecret('client_secret');
    }
    
    /**
     * 测试带有有效Token的请求
     */
    public function testRequest_withValidToken(): void
    {
        // 准备Mock对象
        $token = new GiteeAccessToken();
        $token->setAccessToken('valid_token');
        
        // 设置Token仓库Mock返回值
        $this->tokenRepository->expects($this->once())
            ->method('findLatestByUserAndApplication')
            ->with($this->userId, $this->application->getId())
            ->willReturn($token);
        
        // 设置HTTP客户端Mock
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn(['key' => 'value']);
        
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://gitee.com/api/v5/test',
                $this->callback(function ($options) {
                    return isset($options['headers']['Authorization']) &&
                           $options['headers']['Authorization'] === 'Bearer valid_token';
                })
            )
            ->willReturn($response);
        
        // 执行测试
        $result = $this->apiClient->request('GET', '/test', [], $this->userId, $this->application);
        
        // 验证结果
        $this->assertEquals(['key' => 'value'], $result);
    }
    
    /**
     * 测试不带Token的请求
     */
    public function testRequest_withoutToken(): void
    {
        // 设置HTTP客户端Mock
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn(['key' => 'value']);
        
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://gitee.com/api/v5/test', [])
            ->willReturn($response);
        
        // 执行测试
        $result = $this->apiClient->request('GET', '/test');
        
        // 验证结果
        $this->assertEquals(['key' => 'value'], $result);
    }
    
    /**
     * 测试API请求失败的情况
     */
    public function testRequest_whenRequestFails(): void
    {
        // 设置HTTP客户端Mock抛出异常
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Request failed'));
        
        // 执行测试并验证异常
        $this->expectException(GiteeApiException::class);
        $this->expectExceptionMessage('API请求失败: Request failed');
        
        $this->apiClient->request('GET', '/test');
    }
    
    /**
     * 测试获取用户信息
     *
     * 由于GiteeApiClient被标记为final，我们不能直接mock它
     * 所以我们将直接测试真实对象的行为
     */
    public function testGetUser(): void
    {
        // 准备测试数据
        $userData = ['login' => 'testuser', 'name' => 'Test User'];
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn($userData);
            
        // 设置HTTP客户端预期
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://gitee.com/api/v5/user', $this->anything())
            ->willReturn($response);
            
        // 执行测试
        $result = $this->apiClient->getUser($this->userId, $this->application);
        
        // 验证结果
        $this->assertEquals($userData, $result);
    }
    
    /**
     * 测试获取仓库列表，当找不到令牌时
     */
    public function testGetRepositories_whenTokenNotFound(): void
    {
        // 设置Token仓库Mock返回空
        $this->tokenRepository->expects($this->once())
            ->method('findLatestByUserAndApplication')
            ->willReturn(null);
        
        // 执行测试并验证异常
        $this->expectException(GiteeApiException::class);
        $this->expectExceptionMessage('未找到有效的访问令牌');
        
        $this->apiClient->getRepositories($this->userId, $this->application);
    }
    
    /**
     * 测试获取仓库列表的请求
     */
    public function testGetRepositories_requestCorrectly(): void
    {
        // 准备测试数据
        $token = new GiteeAccessToken();
        $token->setAccessToken('valid_token');
        
        // 设置Token仓库Mock返回值
        $this->tokenRepository->expects($this->once())
            ->method('findLatestByUserAndApplication')
            ->with($this->userId, $this->application->getId())
            ->willReturn($token);
            
        // 设置HTTP客户端Mock
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([]);
        
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'GET', 
                'https://gitee.com/api/v5/user/repos', 
                $this->callback(function ($options) {
                    return isset($options['query']) && 
                           isset($options['query']['access_token']) && 
                           $options['query']['access_token'] === 'valid_token';
                })
            )
            ->willReturn($response);
            
        // 执行测试
        $this->apiClient->getRepositories($this->userId, $this->application);
    }
    
    /**
     * 测试获取单个仓库信息
     */
    public function testGetRepository(): void
    {
        // 准备测试数据
        $owner = 'owner';
        $repo = 'repo';
        $repoData = ['id' => 123, 'name' => 'repo'];
        
        // 设置HTTP客户端预期
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn($repoData);
            
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', "https://gitee.com/api/v5/repos/$owner/$repo", $this->anything())
            ->willReturn($response);
        
        // 执行测试
        $result = $this->apiClient->getRepository($owner, $repo, $this->userId, $this->application);
        
        // 验证结果
        $this->assertEquals($repoData, $result);
    }
    
    /**
     * 测试获取分支列表
     */
    public function testGetBranches(): void
    {
        // 准备测试数据
        $owner = 'owner';
        $repo = 'repo';
        $branchData = [['name' => 'main'], ['name' => 'develop']];
        
        // 设置HTTP客户端预期
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn($branchData);
            
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', "https://gitee.com/api/v5/repos/$owner/$repo/branches", $this->anything())
            ->willReturn($response);
        
        // 执行测试
        $result = $this->apiClient->getBranches($owner, $repo, $this->userId, $this->application);
        
        // 验证结果
        $this->assertEquals($branchData, $result);
    }
    
    /**
     * 测试获取Issue列表
     */
    public function testGetIssues(): void
    {
        // 准备测试数据
        $owner = 'owner';
        $repo = 'repo';
        $params = ['state' => 'open'];
        $issueData = [['id' => 1, 'title' => 'Issue 1'], ['id' => 2, 'title' => 'Issue 2']];
        
        // 设置HTTP客户端预期
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn($issueData);
            
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', "https://gitee.com/api/v5/repos/$owner/$repo/issues", $this->callback(function($options) use ($params) {
                return isset($options['query']) && $options['query'] === $params;
            }))
            ->willReturn($response);
        
        // 执行测试
        $result = $this->apiClient->getIssues($owner, $repo, $params, $this->userId, $this->application);
        
        // 验证结果
        $this->assertEquals($issueData, $result);
    }
    
    /**
     * 测试获取Pull Request列表
     */
    public function testGetPullRequests(): void
    {
        // 准备测试数据
        $owner = 'owner';
        $repo = 'repo';
        $params = ['state' => 'open'];
        $prData = [['id' => 1, 'title' => 'PR 1'], ['id' => 2, 'title' => 'PR 2']];
        
        // 设置HTTP客户端预期
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn($prData);
            
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', "https://gitee.com/api/v5/repos/$owner/$repo/pulls", $this->callback(function($options) use ($params) {
                return isset($options['query']) && $options['query'] === $params;
            }))
            ->willReturn($response);
        
        // 执行测试
        $result = $this->apiClient->getPullRequests($owner, $repo, $params, $this->userId, $this->application);
        
        // 验证结果
        $this->assertEquals($prData, $result);
    }
    
    /**
     * 测试获取仓库列表的请求失败情况
     */
    public function testGetRepositories_throwsExceptionWhenRequestFails(): void
    {
        // 准备测试数据
        $token = new GiteeAccessToken();
        $token->setAccessToken('valid_token');
        
        // 设置Token仓库Mock返回值
        $this->tokenRepository->expects($this->once())
            ->method('findLatestByUserAndApplication')
            ->with($this->userId, $this->application->getId())
            ->willReturn($token);
            
        // 设置HTTP客户端Mock抛出异常
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Request failed'));
            
        // 执行测试并验证异常
        $this->expectException(GiteeApiException::class);
        $this->expectExceptionMessage('API请求失败: Request failed');
        
        $this->apiClient->getRepositories($this->userId, $this->application);
    }
} 