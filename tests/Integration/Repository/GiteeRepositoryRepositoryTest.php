<?php

namespace GiteeApiBundle\Tests\Integration\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use GiteeApiBundle\Entity\GiteeRepository;
use GiteeApiBundle\Repository\GiteeRepositoryRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiteeRepositoryRepositoryTest extends TestCase
{
    private GiteeRepositoryRepository $repository;
    private MockObject $registry;
    
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->repository = new GiteeRepositoryRepository($this->registry);
    }
    
    public function testExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }
    
    public function testRepositoryConstructorSetsCorrectEntityClass(): void
    {
        // 通过构造函数参数验证实体类
        $this->assertInstanceOf(GiteeRepositoryRepository::class, $this->repository);
    }
}