<?php

declare(strict_types=1);

namespace GiteeApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Entity\GiteeRepository;

final class GiteeRepositoryFixtures extends Fixture implements DependentFixtureInterface
{
    public const REFERENCE_REPOSITORY = 'gitee_repository';

    public function load(ObjectManager $manager): void
    {
        $application = $this->getReference(GiteeApplicationFixtures::REFERENCE_APPLICATION, GiteeApplication::class);

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('test_user');
        $repository->setFullName('test_user/test_repo');
        $repository->setName('test_repo');
        $repository->setOwner('test_user');
        $repository->setDescription('Test repository description');
        $repository->setDefaultBranch('main');
        $repository->setPrivate(false);
        $repository->setFork(false);
        $repository->setHtmlUrl('https://gitee.com/test_user/test_repo');
        $repository->setSshUrl('git@gitee.com:test_user/test_repo.git');
        $repository->setPushTime(new \DateTimeImmutable('2024-01-01T00:00:00Z'));

        $manager->persist($repository);

        $this->addReference(self::REFERENCE_REPOSITORY, $repository);
    }

    public function getDependencies(): array
    {
        return [
            GiteeApplicationFixtures::class,
        ];
    }
}
