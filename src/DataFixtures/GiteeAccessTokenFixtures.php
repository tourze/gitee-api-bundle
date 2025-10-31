<?php

declare(strict_types=1);

namespace GiteeApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use GiteeApiBundle\Entity\GiteeAccessToken;
use GiteeApiBundle\Entity\GiteeApplication;

final class GiteeAccessTokenFixtures extends Fixture implements DependentFixtureInterface
{
    public const REFERENCE_ACCESS_TOKEN = 'gitee_access_token';

    public function load(ObjectManager $manager): void
    {
        $application = $this->getReference(GiteeApplicationFixtures::REFERENCE_APPLICATION, GiteeApplication::class);

        $accessToken = new GiteeAccessToken();
        $accessToken->setApplication($application);
        $accessToken->setUserId('test_user');
        $accessToken->setAccessToken('test_access_token_123');
        $accessToken->setRefreshToken('test_refresh_token_456');
        $accessToken->setExpireTime(new \DateTimeImmutable('+1 hour'));
        $accessToken->setGiteeUsername('test_user');

        $manager->persist($accessToken);
        $manager->flush();

        $this->addReference(self::REFERENCE_ACCESS_TOKEN, $accessToken);
    }

    public function getDependencies(): array
    {
        return [
            GiteeApplicationFixtures::class,
        ];
    }
}
