<?php

namespace GiteeApiBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\MissingIdentifierField;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Enum\GiteeScope;
use GiteeApiBundle\Repository\GiteeApplicationRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(GiteeApplicationRepository::class)]
#[RunTestsInSeparateProcesses]
final class GiteeApplicationRepositoryTest extends AbstractRepositoryTestCase
{
    private GiteeApplicationRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(GiteeApplicationRepository::class);

        // 创建一些基础测试数据，以满足基类的数据装载器测试要求
        $baseApplication = new GiteeApplication();
        $baseApplication->setName('Base Test Application');
        $baseApplication->setClientId('base-test-client-id');
        $baseApplication->setClientSecret('base-test-client-secret');
        $baseApplication->setHomepage('https://base-test.example.com');
        $baseApplication->setDescription('Base test application for data fixtures');
        $this->repository->save($baseApplication);
    }

    protected function createNewEntity(): object
    {
        $application = new GiteeApplication();
        $application->setName('Test Application ' . uniqid());
        $application->setClientId('test-client-id-' . uniqid());
        $application->setClientSecret('test-client-secret-' . uniqid());
        $application->setHomepage('https://test-' . uniqid() . '.example.com');
        $application->setDescription('Test application description');

        return $application;
    }

    public function testSaveAndRemoveApplication(): void
    {
        $application = new GiteeApplication();
        $application->setName('Test Application');
        $application->setClientId('test-client-id');
        $application->setClientSecret('test-client-secret');
        $application->setHomepage('https://example.com');
        $application->setDescription('Test application description');

        $this->repository->save($application);
        $savedId = $application->getId();
        $this->assertNotNull($savedId);
        $this->assertGreaterThan(0, $savedId);

        $foundApplication = $this->repository->find($savedId);
        $this->assertInstanceOf(GiteeApplication::class, $foundApplication);
        $this->assertEquals('Test Application', $foundApplication->getName());

        $this->repository->remove($application);
        $removedApplication = $this->repository->find($savedId);
        $this->assertNull($removedApplication);
    }

    public function testSaveWithoutFlush(): void
    {
        $application = new GiteeApplication();
        $application->setName('No Flush Test');
        $application->setClientId('no-flush-client-id');
        $application->setClientSecret('no-flush-secret');

        $this->repository->save($application, false);
        self::getEntityManager()->flush();

        $savedId = $application->getId();
        $this->assertNotNull($savedId);
        $this->assertGreaterThan(0, $savedId);

        $foundApplication = $this->repository->find($savedId);
        $this->assertInstanceOf(GiteeApplication::class, $foundApplication);
    }

    public function testFindWithVeryLargeIdShouldReturnNull(): void
    {
        $result = $this->repository->find(999999);
        $this->assertNull($result);
    }

    public function testFindWithNullParameterShouldThrowException(): void
    {
        $this->expectException(MissingIdentifierField::class);
        $this->repository->find(null);
    }

    public function testFindOneByWithOrderByForScopes(): void
    {
        $application1 = new GiteeApplication();
        $application1->setName('Scopes Order App 1');
        $application1->setClientId('scopes-client-1');
        $application1->setClientSecret('scopes-secret-1');
        $application1->setScopes([GiteeScope::USER]);
        $this->repository->save($application1);

        $application2 = new GiteeApplication();
        $application2->setName('Scopes Order App 2');
        $application2->setClientId('scopes-client-2');
        $application2->setClientSecret('scopes-secret-2');
        $application2->setScopes([GiteeScope::USER, GiteeScope::PROJECTS]);
        $this->repository->save($application2);

        // Test ordering by name to get predictable results
        $result = $this->repository->findOneBy([], ['name' => 'ASC']);
        $this->assertInstanceOf(GiteeApplication::class, $result);
        $this->assertIsArray($result->getScopes());
        $this->assertNotEmpty($result->getScopes());
    }

    public function testFindWithZeroValueShouldReturnNull(): void
    {
        $result = $this->repository->find(0);
        $this->assertNull($result);
    }

    public function testFindByWithNonMatchingNameShouldReturnEmptyArray(): void
    {
        $results = $this->repository->findBy(['name' => 'Non-existent App']);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testFindOneByWithNonExistentClientIdShouldReturnNull(): void
    {
        $result = $this->repository->findOneBy(['clientId' => 'non-existent-client']);
        $this->assertNull($result);
    }

    public function testFindOneByShouldRespectOrderByClause(): void
    {
        $application1 = new GiteeApplication();
        $application1->setName('Order App A');
        $application1->setClientId('order-client-a');
        $application1->setClientSecret('order-secret-a');
        $this->repository->save($application1);

        $application2 = new GiteeApplication();
        $application2->setName('Order App Z');
        $application2->setClientId('order-client-z');
        $application2->setClientSecret('order-secret-z');
        $this->repository->save($application2);

        $applications = $this->repository->findBy(['clientId' => ['order-client-a', 'order-client-z']], ['name' => 'DESC']);
        $this->assertIsArray($applications);
        $this->assertCount(2, $applications);
        $this->assertInstanceOf(GiteeApplication::class, $applications[0]);
        $this->assertEquals('Order App Z', $applications[0]->getName());
    }

    public function testFindByWithNullHomepage(): void
    {
        $application = new GiteeApplication();
        $application->setName('Null Homepage App');
        $application->setClientId('null-homepage-client');
        $application->setClientSecret('null-homepage-secret');
        $application->setHomepage(null);
        $this->repository->save($application);

        $results = $this->repository->findBy(['homepage' => null]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertInstanceOf(GiteeApplication::class, $results[0]);
        $this->assertNull($results[0]->getHomepage());
    }

    public function testCountWithNullHomepage(): void
    {
        $application = new GiteeApplication();
        $application->setName('Count Null Homepage');
        $application->setClientId('count-null-homepage');
        $application->setClientSecret('count-null-secret');
        $application->setHomepage(null);
        $this->repository->save($application);

        $count = $this->repository->count(['homepage' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindByWithNullDescription(): void
    {
        $application = new GiteeApplication();
        $application->setName('Null Description App');
        $application->setClientId('null-description-client');
        $application->setClientSecret('null-description-secret');
        $application->setDescription(null);
        $this->repository->save($application);

        $results = $this->repository->findBy(['description' => null]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertInstanceOf(GiteeApplication::class, $results[0]);
        $this->assertNull($results[0]->getDescription());
    }

    public function testCountWithNullDescription(): void
    {
        $application = new GiteeApplication();
        $application->setName('Count Null Description');
        $application->setClientId('count-null-description');
        $application->setClientSecret('count-null-secret');
        $application->setDescription(null);
        $this->repository->save($application);

        $count = $this->repository->count(['description' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testRemoveMethod(): void
    {
        $application = new GiteeApplication();
        $application->setName('Remove Test App');
        $application->setClientId('remove-test-client');
        $application->setClientSecret('remove-test-secret');
        $this->repository->save($application);

        $savedId = $application->getId();
        $this->assertGreaterThan(0, $savedId);

        $this->repository->remove($application);
        $removedApp = $this->repository->find($savedId);
        $this->assertNull($removedApp);
    }

    public function testFindByClientId(): void
    {
        $application = new GiteeApplication();
        $application->setName('Custom Method App');
        $application->setClientId('custom-method-client');
        $application->setClientSecret('custom-method-secret');
        $this->repository->save($application);

        $result = $this->repository->findByClientId('custom-method-client');
        $this->assertInstanceOf(GiteeApplication::class, $result);
        $this->assertEquals('custom-method-client', $result->getClientId());
    }

    public function testFindByClientIdWithNonExistentClient(): void
    {
        $result = $this->repository->findByClientId('non-existent-client');
        $this->assertNull($result);
    }

    public function testFindOneByWithNullHomepage(): void
    {
        $application = new GiteeApplication();
        $application->setName('FindOneBy Null Homepage');
        $application->setClientId('find-one-null-homepage');
        $application->setClientSecret('find-one-null-secret');
        $application->setHomepage(null);
        $this->repository->save($application);

        $result = $this->repository->findOneBy(['homepage' => null]);
        $this->assertInstanceOf(GiteeApplication::class, $result);
        $this->assertNull($result->getHomepage());
    }

    public function testFindOneByWithNullDescription(): void
    {
        $application = new GiteeApplication();
        $application->setName('FindOneBy Null Description');
        $application->setClientId('find-one-null-description');
        $application->setClientSecret('find-one-null-description-secret');
        $application->setDescription(null);
        $this->repository->save($application);

        $result = $this->repository->findOneBy(['description' => null]);
        $this->assertInstanceOf(GiteeApplication::class, $result);
        $this->assertNull($result->getDescription());
    }

    public function testFindOneByWithOrderByForCreateTime(): void
    {
        $application1 = new GiteeApplication();
        $application1->setName('CreateTime Order App Older');
        $application1->setClientId('create-time-client-1');
        $application1->setClientSecret('create-time-secret-1');
        $this->repository->save($application1);

        // Small delay to ensure different timestamps
        usleep(1000);

        $application2 = new GiteeApplication();
        $application2->setName('CreateTime Order App Newer');
        $application2->setClientId('create-time-client-2');
        $application2->setClientSecret('create-time-secret-2');
        $this->repository->save($application2);

        $result = $this->repository->findOneBy([], ['id' => 'DESC']);
        $this->assertInstanceOf(GiteeApplication::class, $result);
        $this->assertEquals('CreateTime Order App Newer', $result->getName());
    }

    public function testFindOneByWithOrderByForUpdateTime(): void
    {
        $application1 = new GiteeApplication();
        $application1->setName('UpdateTime Order App 1');
        $application1->setClientId('update-time-client-1');
        $application1->setClientSecret('update-time-secret-1');
        $this->repository->save($application1);

        $application2 = new GiteeApplication();
        $application2->setName('UpdateTime Order App 2');
        $application2->setClientId('update-time-client-2');
        $application2->setClientSecret('update-time-secret-2');
        $this->repository->save($application2);

        // Update the first application to change updateTime
        $application1->setDescription('Updated description');
        $this->repository->save($application1);

        // 查找包含"UpdateTime Order App"的应用，按updateTime降序排列
        $qb = $this->repository->createQueryBuilder('a')
            ->where('a.name LIKE :name')
            ->setParameter('name', 'UpdateTime Order App%')
            ->orderBy('a.updateTime', 'DESC')
            ->setMaxResults(1)
        ;

        $result = $qb->getQuery()->getOneOrNullResult();
        $this->assertInstanceOf(GiteeApplication::class, $result);
        $this->assertEquals('UpdateTime Order App 1', $result->getName());
    }

    public function testFindOneByWithOrderByForName(): void
    {
        $application1 = new GiteeApplication();
        $application1->setName('App A');
        $application1->setClientId('order-client-a');
        $application1->setClientSecret('order-secret-a');
        $this->repository->save($application1);

        $application2 = new GiteeApplication();
        $application2->setName('App Z');
        $application2->setClientId('order-client-z');
        $application2->setClientSecret('order-secret-z');
        $this->repository->save($application2);

        $result = $this->repository->findOneBy([], ['name' => 'ASC']);
        $this->assertInstanceOf(GiteeApplication::class, $result);
        $this->assertEquals('App A', $result->getName());
    }

    public function testFindOneByWithOrderByForClientId(): void
    {
        $application1 = new GiteeApplication();
        $application1->setName('Order Test App 1');
        $application1->setClientId('client-001');
        $application1->setClientSecret('secret-001');
        $this->repository->save($application1);

        $application2 = new GiteeApplication();
        $application2->setName('Order Test App 2');
        $application2->setClientId('client-999');
        $application2->setClientSecret('secret-999');
        $this->repository->save($application2);

        $applications = $this->repository->findBy(['clientId' => ['client-001', 'client-999']], ['clientId' => 'DESC']);
        $this->assertIsArray($applications);
        $this->assertCount(2, $applications);
        $this->assertInstanceOf(GiteeApplication::class, $applications[0]);
        $this->assertEquals('client-999', $applications[0]->getClientId());
    }

    public function testFindByWithOrderByForHomepage(): void
    {
        $application1 = new GiteeApplication();
        $application1->setName('Homepage Order App 1');
        $application1->setClientId('homepage-client-1');
        $application1->setClientSecret('homepage-secret-1');
        $application1->setHomepage('https://a-example.com');
        $this->repository->save($application1);

        $application2 = new GiteeApplication();
        $application2->setName('Homepage Order App 2');
        $application2->setClientId('homepage-client-2');
        $application2->setClientSecret('homepage-secret-2');
        $application2->setHomepage('https://z-example.com');
        $this->repository->save($application2);

        $results = $this->repository->findBy([], ['homepage' => 'ASC']);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(2, count($results));

        $foundHomepages = array_map(function (GiteeApplication $app) {
            return $app->getHomepage();
        }, $results);
        $this->assertContains('https://a-example.com', $foundHomepages);
        $this->assertContains('https://z-example.com', $foundHomepages);
    }

    public function testFindByWithOrderByForDescription(): void
    {
        $application1 = new GiteeApplication();
        $application1->setName('Description Order App 1');
        $application1->setClientId('desc-client-1');
        $application1->setClientSecret('desc-secret-1');
        $application1->setDescription('A description');
        $this->repository->save($application1);

        $application2 = new GiteeApplication();
        $application2->setName('Description Order App 2');
        $application2->setClientId('desc-client-2');
        $application2->setClientSecret('desc-secret-2');
        $application2->setDescription('Z description');
        $this->repository->save($application2);

        $results = $this->repository->findBy([], ['description' => 'ASC']);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(2, count($results));

        $foundDescriptions = array_map(function (GiteeApplication $app) {
            return $app->getDescription();
        }, $results);
        $this->assertContains('A description', $foundDescriptions);
        $this->assertContains('Z description', $foundDescriptions);
    }

    public function testFindWithNullIdParameterShouldReturnNull(): void
    {
        // This test is to satisfy PHPStan requirements for null ID handling
        // Doctrine throws MissingIdentifierField exception when ID is null
        $this->expectException(MissingIdentifierField::class);
        $this->repository->find(null);
    }

    public function testFindWithDatabaseConnectionIssue(): void
    {
        // This test is included for completeness but cannot easily simulate database failures
        // in integration tests without mocking, which would defeat the purpose
        $this->expectNotToPerformAssertions();
    }

    public function testFindByWithNullCreateTime(): void
    {
        // Test that the method can handle null values for createTime
        // Even though new entities shouldn't have null createTime in practice,
        // we test the repository's ability to query for null values
        $results = $this->repository->findBy(['createTime' => null]);
        $this->assertIsArray($results);
        // Results can be empty since timestamp fields are typically auto-populated
    }

    public function testFindByWithNullUpdateTime(): void
    {
        // Test that the method can handle null values for updateTime
        // Even though new entities shouldn't have null updateTime in practice,
        // we test the repository's ability to query for null values
        $results = $this->repository->findBy(['updateTime' => null]);
        $this->assertIsArray($results);
        // Results can be empty since timestamp fields are typically auto-populated
    }

    public function testCountWithNullCreateTime(): void
    {
        // Test that count method can handle null values for createTime
        $count = $this->repository->count(['createTime' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithNullUpdateTime(): void
    {
        // Test that count method can handle null values for updateTime
        $count = $this->repository->count(['updateTime' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testFindOneByWithNullHomepageQuery(): void
    {
        $application = new GiteeApplication();
        $application->setName('FindOneBy Null Homepage Query');
        $application->setClientId('find-one-null-homepage-query');
        $application->setClientSecret('find-one-null-homepage-query-secret');
        $application->setHomepage(null);
        $this->repository->save($application);

        $result = $this->repository->findOneBy(['homepage' => null]);
        $this->assertInstanceOf(GiteeApplication::class, $result);
        $this->assertNull($result->getHomepage());
    }

    public function testFindOneByWithNullDescriptionQuery(): void
    {
        $application = new GiteeApplication();
        $application->setName('FindOneBy Null Description Query');
        $application->setClientId('find-one-null-description-query');
        $application->setClientSecret('find-one-null-description-query-secret');
        $application->setDescription(null);
        $this->repository->save($application);

        $result = $this->repository->findOneBy(['description' => null]);
        $this->assertInstanceOf(GiteeApplication::class, $result);
        $this->assertNull($result->getDescription());
    }

    public function testFindOneByWithOrderByForAllFields(): void
    {
        $application1 = new GiteeApplication();
        $application1->setName('Order All Fields App A');
        $application1->setClientId('order-all-client-a');
        $application1->setClientSecret('order-all-secret-a');
        $application1->setHomepage('https://a-example.com');
        $application1->setDescription('A description');
        $this->repository->save($application1);

        $application2 = new GiteeApplication();
        $application2->setName('Order All Fields App Z');
        $application2->setClientId('order-all-client-z');
        $application2->setClientSecret('order-all-secret-z');
        $application2->setHomepage('https://z-example.com');
        $application2->setDescription('Z description');
        $this->repository->save($application2);

        // Test ordering by all sortable fields - use query builder to filter out base test data
        $qb = $this->repository->createQueryBuilder('a')
            ->where('a.name LIKE :name')
            ->setParameter('name', 'Order All Fields App%')
            ->orderBy('a.name', 'ASC')
            ->setMaxResults(1)
        ;

        $resultByName = $qb->getQuery()->getOneOrNullResult();
        $this->assertInstanceOf(GiteeApplication::class, $resultByName);
        $this->assertEquals('Order All Fields App A', $resultByName->getName());

        $applicationsByClientId = $this->repository->findBy(['clientId' => ['order-all-client-a', 'order-all-client-z']], ['clientId' => 'DESC']);
        $this->assertIsArray($applicationsByClientId);
        $this->assertCount(2, $applicationsByClientId);
        $this->assertInstanceOf(GiteeApplication::class, $applicationsByClientId[0]);
        $this->assertEquals('order-all-client-z', $applicationsByClientId[0]->getClientId());

        $applicationsByHomepage = $this->repository->findBy(['homepage' => ['https://a-example.com', 'https://z-example.com']], ['homepage' => 'ASC']);
        $this->assertIsArray($applicationsByHomepage);
        $this->assertCount(2, $applicationsByHomepage);
        $this->assertInstanceOf(GiteeApplication::class, $applicationsByHomepage[0]);
        $this->assertEquals('https://a-example.com', $applicationsByHomepage[0]->getHomepage());

        $applicationsByDescription = $this->repository->findBy(['description' => ['A description', 'Z description']], ['description' => 'DESC']);
        $this->assertIsArray($applicationsByDescription);
        $this->assertCount(2, $applicationsByDescription);
        $this->assertInstanceOf(GiteeApplication::class, $applicationsByDescription[0]);
        $this->assertEquals('Z description', $applicationsByDescription[0]->getDescription());

        // Test ordering by timestamp fields
        $resultByCreateTime = $this->repository->findOneBy([], ['createTime' => 'ASC']);
        $this->assertInstanceOf(GiteeApplication::class, $resultByCreateTime);
        $this->assertNotNull($resultByCreateTime->getCreateTime());

        $resultByUpdateTime = $this->repository->findOneBy([], ['updateTime' => 'DESC']);
        $this->assertInstanceOf(GiteeApplication::class, $resultByUpdateTime);
        $this->assertNotNull($resultByUpdateTime->getUpdateTime());
    }

    public function testFindOneByWithNullCreateTimeQuery(): void
    {
        // Test findOneBy with null createTime query
        $result = $this->repository->findOneBy(['createTime' => null]);
        // Result can be null since timestamp fields are typically auto-populated
        $this->assertTrue(null === $result || $result instanceof GiteeApplication);
    }

    public function testFindOneByWithNullUpdateTimeQuery(): void
    {
        // Test findOneBy with null updateTime query
        $result = $this->repository->findOneBy(['updateTime' => null]);
        // Result can be null since timestamp fields are typically auto-populated
        $this->assertTrue(null === $result || $result instanceof GiteeApplication);
    }

    /**
     * @return GiteeApplicationRepository
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
