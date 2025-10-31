<?php

declare(strict_types=1);

namespace GiteeApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use GiteeApiBundle\Entity\GiteeRepository;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<GiteeRepository>
 */
#[AsRepository(entityClass: GiteeRepository::class)]
class GiteeRepositoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GiteeRepository::class);
    }

    /**
     * @return GiteeRepository[]
     */
    public function findByUserAndApplication(string $userId, string $applicationId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.userId = :userId')
            ->andWhere('r.application = :applicationId')
            ->setParameter('userId', $userId)
            ->setParameter('applicationId', $applicationId)
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(GiteeRepository $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(GiteeRepository $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
