<?php

declare(strict_types=1);

namespace GiteeApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use GiteeApiBundle\Entity\GiteeApplication;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<GiteeApplication>
 */
#[AsRepository(entityClass: GiteeApplication::class)]
class GiteeApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GiteeApplication::class);
    }

    public function findByClientId(string $clientId): ?GiteeApplication
    {
        $result = $this->findOneBy(['clientId' => $clientId]);

        return $result instanceof GiteeApplication ? $result : null;
    }

    public function save(GiteeApplication $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(GiteeApplication $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
