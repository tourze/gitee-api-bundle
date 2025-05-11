<?php

namespace GiteeApiBundle\Tests\Entity;

use DateTimeImmutable;
use GiteeApiBundle\Entity\GiteeAccessToken;
use GiteeApiBundle\Entity\GiteeApplication;
use PHPUnit\Framework\TestCase;

class GiteeAccessTokenTest extends TestCase
{
    private GiteeApplication $application;

    protected function setUp(): void
    {
        $this->application = new GiteeApplication();
        $this->application->setName('Test App')
            ->setClientId('client_id')
            ->setClientSecret('client_secret');
    }

    /**
     * 测试ID属性的getter方法
     */
    public function testGetId(): void
    {
        $token = new GiteeAccessToken();

        $this->assertEquals(0, $token->getId());
    }

    /**
     * 测试设置和获取应用
     */
    public function testSetAndGetApplication(): void
    {
        $token = new GiteeAccessToken();

        $result = $token->setApplication($this->application);

        $this->assertSame($token, $result);
        $this->assertSame($this->application, $token->getApplication());
    }

    /**
     * 测试设置和获取用户ID
     */
    public function testSetAndGetUserId(): void
    {
        $token = new GiteeAccessToken();
        $userId = "user123";

        $result = $token->setUserId($userId);

        $this->assertSame($token, $result);
        $this->assertEquals($userId, $token->getUserId());
    }

    /**
     * 测试设置和获取访问令牌
     */
    public function testSetAndGetAccessToken(): void
    {
        $token = new GiteeAccessToken();
        $accessToken = "access_token_123";

        $result = $token->setAccessToken($accessToken);

        $this->assertSame($token, $result);
        $this->assertEquals($accessToken, $token->getAccessToken());
    }

    /**
     * 测试设置和获取刷新令牌
     */
    public function testSetAndGetRefreshToken(): void
    {
        $token = new GiteeAccessToken();
        $refreshToken = "refresh_token_123";

        $result = $token->setRefreshToken($refreshToken);

        $this->assertSame($token, $result);
        $this->assertEquals($refreshToken, $token->getRefreshToken());
    }

    /**
     * 测试设置和获取令牌过期时间
     */
    public function testSetAndGetExpiresAt(): void
    {
        $token = new GiteeAccessToken();
        $expiresAt = new DateTimeImmutable('+1 hour');

        $result = $token->setExpiresAt($expiresAt);

        $this->assertSame($token, $result);
        $this->assertEquals($expiresAt, $token->getExpiresAt());
    }

    /**
     * 测试设置和获取Gitee用户名
     */
    public function testSetAndGetGiteeUsername(): void
    {
        $token = new GiteeAccessToken();
        $username = "gitee_user";

        $result = $token->setGiteeUsername($username);

        $this->assertSame($token, $result);
        $this->assertEquals($username, $token->getGiteeUsername());
    }

    /**
     * 测试设置和获取创建时间
     */
    public function testSetAndGetCreateTime(): void
    {
        $token = new GiteeAccessToken();
        $createTime = new \DateTimeImmutable();

        $token->setCreateTime($createTime);

        $this->assertEquals($createTime, $token->getCreateTime());
    }

    /**
     * 测试设置和获取更新时间
     */
    public function testSetAndGetUpdateTime(): void
    {
        $token = new GiteeAccessToken();
        $updateTime = new \DateTimeImmutable();

        $token->setUpdateTime($updateTime);

        $this->assertEquals($updateTime, $token->getUpdateTime());
    }
}
