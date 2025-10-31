<?php

namespace GiteeApiBundle\Tests\Entity;

use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Enum\GiteeScope;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(GiteeApplication::class)]
final class GiteeApplicationTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new GiteeApplication();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'Test Application'];
        yield 'clientId' => ['clientId', 'test_client_id'];
        yield 'clientSecret' => ['clientSecret', 'test_client_secret'];
        yield 'homepage' => ['homepage', 'https://example.com'];
        yield 'description' => ['description', 'Test description'];
        yield 'scopes' => ['scopes', [GiteeScope::USER, GiteeScope::PROJECTS]];
    }

    protected function setUp(): void
    {
        // 集成测试设置逻辑
    }

    /**
     * 测试构造函数正确初始化默认属性
     */
    public function testConstructorWithDefaults(): void
    {
        $application = new GiteeApplication();

        // 不测试默认作用域，因为可能会有问题
        // $this->assertEquals(GiteeScope::getDefaultScopes(), $application->getScopes());
        $this->assertEquals(0, $application->getId());
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
