<?php

namespace GiteeApiBundle\Tests\Entity;

use GiteeApiBundle\Entity\GiteeAccessToken;
use GiteeApiBundle\Entity\GiteeApplication;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(GiteeAccessToken::class)]
final class GiteeAccessTokenTest extends AbstractEntityTestCase
{
    private GiteeApplication $application;

    protected function createEntity(): object
    {
        return new GiteeAccessToken();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'accessToken' => ['accessToken', 'test_access_token'];
        yield 'refreshToken' => ['refreshToken', 'test_refresh_token'];
        yield 'userId' => ['userId', 'user123'];
        yield 'giteeUsername' => ['giteeUsername', 'test_user'];
        yield 'expireTime' => ['expireTime', new \DateTimeImmutable('+1 hour')];
    }

    protected function setUp(): void
    {
        $this->application = new GiteeApplication();
        $this->application->setName('Test App');
        $this->application->setClientId('client_id');
        $this->application->setClientSecret('client_secret');
    }

    /**
     * 测试ID属性的getter方法
     */
    public function testGetId(): void
    {
        $token = new GiteeAccessToken();

        $this->assertEquals(0, $token->getId());
    }

    public function testSetAndGetApplication(): void
    {
        $token = new GiteeAccessToken();
        $application = new GiteeApplication();
        $application->setName('Test App');
        $application->setClientId('client_id');
        $application->setClientSecret('client_secret');

        $token->setApplication($application);

        $this->assertSame($application, $token->getApplication());
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
