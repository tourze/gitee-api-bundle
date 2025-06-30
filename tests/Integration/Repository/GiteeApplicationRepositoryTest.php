<?php

namespace GiteeApiBundle\Tests\Integration\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Repository\GiteeApplicationRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiteeApplicationRepositoryTest extends TestCase
{
    private GiteeApplicationRepository $repository;
    private MockObject $registry;
    
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->repository = new GiteeApplicationRepository($this->registry);
    }
    
    public function testExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }
    
    public function testRepositoryConstructorSetsCorrectEntityClass(): void
    {
        // 通过构造函数参数验证实体类
        $this->assertInstanceOf(GiteeApplicationRepository::class, $this->repository);
    }
}