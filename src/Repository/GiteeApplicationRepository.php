<?php

namespace GiteeApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use GiteeApiBundle\Entity\GiteeApplication;

/**
 * @method GiteeApplication|null find($id, $lockMode = null, $lockVersion = null)
 * @method GiteeApplication|null findOneBy(array $criteria, array $orderBy = null)
 * @method GiteeApplication[] findAll()
 * @method GiteeApplication[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GiteeApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GiteeApplication::class);
    }

    public function findByClientId(string $clientId): ?GiteeApplication
    {
        return $this->findOneBy(['clientId' => $clientId]);
    }
}
