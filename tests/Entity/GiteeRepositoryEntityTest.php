<?php

namespace GiteeApiBundle\Tests\Entity;

use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Entity\GiteeRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * GiteeRepository 实体测试类
 *
 * 测试 GiteeRepository 实体的所有属性和方法
 *
 * @internal
 */
#[CoversClass(GiteeRepository::class)]
final class GiteeRepositoryEntityTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new GiteeRepository();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'test-repository'];
        yield 'fullName' => ['fullName', 'owner/test-repository'];
        yield 'owner' => ['owner', 'repository-owner'];
        yield 'private' => ['private', true];
        yield 'description' => ['description', 'Test repository description'];
        yield 'defaultBranch' => ['defaultBranch', 'main'];
        yield 'fork' => ['fork', false];
        yield 'htmlUrl' => ['htmlUrl', 'https://gitee.com/owner/test-repository'];
        yield 'sshUrl' => ['sshUrl', 'git@gitee.com:owner/test-repository.git'];
        yield 'pushTime' => ['pushTime', new \DateTimeImmutable()];
        yield 'userId' => ['userId', 'user123'];
    }

    protected function setUp(): void
    {
        // 集成测试设置逻辑
    }

    /**
     * 测试ID属性的getter方法
     */
    public function testGetId(): void
    {
        $repository = new GiteeRepository();

        $this->assertEquals(0, $repository->getId());
    }

    public function testSetAndGetApplication(): void
    {
        $repository = new GiteeRepository();
        $application = $this->createMock(GiteeApplication::class);

        $repository->setApplication($application);

        $this->assertSame($application, $repository->getApplication());
    }

    /**
     * 测试toString方法
     */
    public function testToString(): void
    {
        $repository = new GiteeRepository();
        $fullName = 'owner/test-repository';
        $repository->setFullName($fullName);

        $this->assertEquals($fullName, (string) $repository);
        $this->assertEquals($fullName, $repository->__toString());
    }
}
