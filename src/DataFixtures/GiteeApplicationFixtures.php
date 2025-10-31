<?php

declare(strict_types=1);

namespace GiteeApiBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Enum\GiteeScope;

final class GiteeApplicationFixtures extends Fixture
{
    public const REFERENCE_APPLICATION = 'gitee_application';

    public function load(ObjectManager $manager): void
    {
        $application = new GiteeApplication();
        $application->setName('Test Gitee Application');
        $application->setClientId('test_client_id_123');
        $application->setClientSecret('test_client_secret_456');
        $application->setScopes([GiteeScope::USER, GiteeScope::PROJECTS]);

        $manager->persist($application);
        $manager->flush();

        $this->addReference(self::REFERENCE_APPLICATION, $application);
    }
}
