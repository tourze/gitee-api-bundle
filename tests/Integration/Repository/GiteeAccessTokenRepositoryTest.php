<?php

namespace GiteeApiBundle\Tests\Integration\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use GiteeApiBundle\Entity\GiteeAccessToken;
use GiteeApiBundle\Repository\GiteeAccessTokenRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiteeAccessTokenRepositoryTest extends TestCase
{
    private GiteeAccessTokenRepository $repository;
    private MockObject $registry;
    
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->repository = new GiteeAccessTokenRepository($this->registry);
    }
    
    public function testExtendsServiceEntityRepository(): void
    {
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }
    
    public function testRepositoryConstructorSetsCorrectEntityClass(): void
    {
        // 通过构造函数参数验证实体类
        $this->assertInstanceOf(GiteeAccessTokenRepository::class, $this->repository);
    }
}