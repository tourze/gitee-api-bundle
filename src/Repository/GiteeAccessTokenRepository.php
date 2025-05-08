<?php

namespace GiteeApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use GiteeApiBundle\Entity\GiteeAccessToken;

/**
 * @method GiteeAccessToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method GiteeAccessToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method GiteeAccessToken[] findAll()
 * @method GiteeAccessToken[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GiteeAccessTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GiteeAccessToken::class);
    }

    public function findByUserId(string $userId): ?GiteeAccessToken
    {
        return $this->findOneBy(['userId' => $userId]);
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
            ->getOneOrNullResult();
    }
}
