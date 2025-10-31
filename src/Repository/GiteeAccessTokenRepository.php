<?php

declare(strict_types=1);

namespace GiteeApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use GiteeApiBundle\Entity\GiteeAccessToken;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<GiteeAccessToken>
 */
#[AsRepository(entityClass: GiteeAccessToken::class)]
class GiteeAccessTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GiteeAccessToken::class);
    }

    public function findByUserId(string $userId): ?GiteeAccessToken
    {
        $result = $this->findOneBy(['userId' => $userId]);

        return $result instanceof GiteeAccessToken ? $result : null;
    }

    public function findLatestByUserAndApplication(string $userId, string $applicationId): ?GiteeAccessToken
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.userId = :userId')
            ->andWhere('t.application = :applicationId')
            ->orderBy('t.createTime', 'DESC')
            ->setParameter('userId', $userId)
            ->setParameter('applicationId', $applicationId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function save(GiteeAccessToken $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(GiteeAccessToken $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
