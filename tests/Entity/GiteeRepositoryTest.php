<?php

namespace GiteeApiBundle\Tests\Entity;

use DateTimeImmutable;
use GiteeApiBundle\Entity\GiteeRepository;
use PHPUnit\Framework\TestCase;

class GiteeRepositoryTest extends TestCase
{
    /**
     * 测试ID属性的getter方法
     */
    public function testGetId(): void
    {
        $repository = new GiteeRepository();

        $this->assertEquals(0, $repository->getId());
    }

    /**
     * 测试设置和获取仓库外部ID - 使用id属性
     */
    public function testSetAndGetId(): void
    {
        $repository = new GiteeRepository();
        $externalId = 12345;

        // 注意：由于实体ID是自动生成的，我们不直接设置它
        // 这个测试只是确认getter方法正常工作

        $this->assertEquals(0, $repository->getId());
    }

    /**
     * 测试设置和获取仓库名称
     */
    public function testSetAndGetName(): void
    {
        $repository = new GiteeRepository();
        $name = "test-repository";

        $result = $repository->setName($name);

        $this->assertSame($repository, $result);
        $this->assertEquals($name, $repository->getName());
    }

    /**
     * 测试设置和获取仓库完整名称
     */
    public function testSetAndGetFullName(): void
    {
        $repository = new GiteeRepository();
        $fullName = "owner/test-repository";

        $result = $repository->setFullName($fullName);

        $this->assertSame($repository, $result);
        $this->assertEquals($fullName, $repository->getFullName());
    }

    /**
     * 测试设置和获取仓库所有者
     */
    public function testSetAndGetOwner(): void
    {
        $repository = new GiteeRepository();
        $owner = "repository-owner";

        $result = $repository->setOwner($owner);

        $this->assertSame($repository, $result);
        $this->assertEquals($owner, $repository->getOwner());
    }

    /**
     * 测试设置和获取私有仓库标志
     */
    public function testSetAndGetPrivate(): void
    {
        $repository = new GiteeRepository();

        // 测试设置为true
        $result = $repository->setPrivate(true);
        $this->assertSame($repository, $result);
        $this->assertTrue($repository->isPrivate());

        // 测试设置为false
        $repository->setPrivate(false);
        $this->assertFalse($repository->isPrivate());
    }

    /**
     * 测试设置和获取仓库描述
     */
    public function testSetAndGetDescription(): void
    {
        $repository = new GiteeRepository();
        $description = "This is a test repository description";

        $result = $repository->setDescription($description);

        $this->assertSame($repository, $result);
        $this->assertEquals($description, $repository->getDescription());

        // 测试空描述
        $repository->setDescription(null);
        $this->assertNull($repository->getDescription());
    }

    /**
     * 测试设置和获取仓库HTML URL
     */
    public function testSetAndGetHtmlUrl(): void
    {
        $repository = new GiteeRepository();
        $htmlUrl = "https://gitee.com/owner/test-repository";

        $result = $repository->setHtmlUrl($htmlUrl);

        $this->assertSame($repository, $result);
        $this->assertEquals($htmlUrl, $repository->getHtmlUrl());
    }

    /**
     * 测试设置和获取SSH URL
     */
    public function testSetAndGetSshUrl(): void
    {
        $repository = new GiteeRepository();
        $sshUrl = "git@gitee.com:owner/test-repository.git";

        $result = $repository->setSshUrl($sshUrl);

        $this->assertSame($repository, $result);
        $this->assertEquals($sshUrl, $repository->getSshUrl());
    }

    /**
     * 测试设置和获取默认分支
     */
    public function testSetAndGetDefaultBranch(): void
    {
        $repository = new GiteeRepository();
        $defaultBranch = "main";

        $result = $repository->setDefaultBranch($defaultBranch);

        $this->assertSame($repository, $result);
        $this->assertEquals($defaultBranch, $repository->getDefaultBranch());
    }

    /**
     * 测试设置和获取创建时间
     */
    public function testSetAndGetCreateTime(): void
    {
        $repository = new GiteeRepository();
        $createTime = new DateTimeImmutable();

        $repository->setCreateTime($createTime);

        $this->assertEquals($createTime, $repository->getCreateTime());
    }

    /**
     * 测试设置和获取更新时间
     */
    public function testSetAndGetUpdateTime(): void
    {
        $repository = new GiteeRepository();
        $updateTime = new DateTimeImmutable();

        $repository->setUpdateTime($updateTime);

        $this->assertEquals($updateTime, $repository->getUpdateTime());
    }
}
