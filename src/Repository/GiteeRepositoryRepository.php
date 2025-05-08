<?php

namespace GiteeApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use GiteeApiBundle\Entity\GiteeRepository;

/**
 * @extends ServiceEntityRepository<GiteeRepository>
 *
 * @method GiteeRepository|null find($id, $lockMode = null, $lockVersion = null)
 * @method GiteeRepository|null findOneBy(array $criteria, array $orderBy = null)
 * @method GiteeRepository[]    findAll()
 * @method GiteeRepository[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
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
            ->getResult();
    }
}
