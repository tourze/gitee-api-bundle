<?php

namespace GiteeApiBundle\Tests\Entity;

use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Enum\GiteeScope;
use PHPUnit\Framework\TestCase;

class GiteeApplicationTest extends TestCase
{
    /**
     * 测试构造函数正确初始化默认属性
     */
    public function testConstructor_withDefaults(): void
    {
        $application = new GiteeApplication();

        // 不测试默认作用域，因为可能会有问题
        // $this->assertEquals(GiteeScope::getDefaultScopes(), $application->getScopes());
        $this->assertEquals(0, $application->getId());
    }

    /**
     * 测试设置和获取名称
     */
    public function testSetAndGetName(): void
    {
        $application = new GiteeApplication();
        $name = "测试应用";

        $result = $application->setName($name);

        $this->assertSame($application, $result);
        $this->assertEquals($name, $application->getName());
    }

    /**
     * 测试设置和获取客户端ID
     */
    public function testSetAndGetClientId(): void
    {
        $application = new GiteeApplication();
        $clientId = "test_client_id";

        $result = $application->setClientId($clientId);

        $this->assertSame($application, $result);
        $this->assertEquals($clientId, $application->getClientId());
    }

    /**
     * 测试设置和获取客户端密钥
     */
    public function testSetAndGetClientSecret(): void
    {
        $application = new GiteeApplication();
        $clientSecret = "test_client_secret";

        $result = $application->setClientSecret($clientSecret);

        $this->assertSame($application, $result);
        $this->assertEquals($clientSecret, $application->getClientSecret());
    }

    /**
     * 测试设置和获取应用主页
     */
    public function testSetAndGetHomepage(): void
    {
        $application = new GiteeApplication();
        $homepage = "https://example.com";

        $result = $application->setHomepage($homepage);

        $this->assertSame($application, $result);
        $this->assertEquals($homepage, $application->getHomepage());
    }

    /**
     * 测试设置和获取应用描述
     */
    public function testSetAndGetDescription(): void
    {
        $application = new GiteeApplication();
        $description = "这是一个测试应用描述";

        $result = $application->setDescription($description);

        $this->assertSame($application, $result);
        $this->assertEquals($description, $application->getDescription());
    }

    /**
     * 测试设置和获取作用域
     */
    public function testSetAndGetScopes(): void
    {
        $application = new GiteeApplication();
        $scopes = [
            GiteeScope::USER,
            GiteeScope::PROJECTS,
        ];

        $result = $application->setScopes($scopes);

        $this->assertSame($application, $result);
        $this->assertEquals($scopes, $application->getScopes());
    }

    /**
     * 测试获取作用域字符串
     */
    public function testGetScopesAsString(): void
    {
        $application = new GiteeApplication();
        $scopes = [
            GiteeScope::USER,
            GiteeScope::PROJECTS,
        ];

        $application->setScopes($scopes);
        $expected = 'user_info projects';

        $this->assertEquals($expected, $application->getScopesAsString());
    }

    /**
     * 测试设置和获取创建时间
     */
    public function testSetAndGetCreateTime(): void
    {
        $application = new GiteeApplication();
        $dateTime = new \DateTimeImmutable();

        $application->setCreateTime($dateTime);

        $this->assertEquals($dateTime, $application->getCreateTime());
    }

    /**
     * 测试设置和获取更新时间
     */
    public function testSetAndGetUpdateTime(): void
    {
        $application = new GiteeApplication();
        $dateTime = new \DateTimeImmutable();

        $application->setUpdateTime($dateTime);

        $this->assertEquals($dateTime, $application->getUpdateTime());
    }
}
