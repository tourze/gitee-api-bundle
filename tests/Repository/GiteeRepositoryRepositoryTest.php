<?php

namespace GiteeApiBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\MissingIdentifierField;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Entity\GiteeRepository;
use GiteeApiBundle\Repository\GiteeRepositoryRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(GiteeRepositoryRepository::class)]
#[RunTestsInSeparateProcesses]
final class GiteeRepositoryRepositoryTest extends AbstractRepositoryTestCase
{
    private GiteeRepositoryRepository $repository;

    private GiteeApplication $testApplication;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(GiteeRepositoryRepository::class);

        // 如果当前测试是数据库连接测试，跳过数据加载操作
        if ($this->isTestingDatabaseConnection()) {
            return;
        }

        // 创建测试用的应用
        $this->testApplication = new GiteeApplication();
        $this->testApplication->setName('Test Application');
        $this->testApplication->setClientId('test-client-id');
        $this->testApplication->setClientSecret('test-client-secret');
        self::getEntityManager()->persist($this->testApplication);
        self::getEntityManager()->flush();

        // 清理实体管理器状态，避免影响数据库连接测试
        try {
            self::getEntityManager()->clear();
        } catch (\Exception $e) {
            // 忽略清理错误
        }
    }

    private function isTestingDatabaseConnection(): bool
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($backtrace as $trace) {
            if (str_contains($trace['function'], 'testFindWhenDatabaseIsUnavailable')) {
                return true;
            }
            if (str_contains($trace['function'], 'testFindByWhenDatabaseIsUnavailable')) {
                return true;
            }
            if (str_contains($trace['function'], 'testCountWhenDatabaseIsUnavailable')) {
                return true;
            }
            if (str_contains($trace['function'], 'testFindAllWhenDatabaseIsUnavailable')) {
                return true;
            }
        }

        return false;
    }

    private function createTestApplication(): GiteeApplication
    {
        $uniqueId = uniqid();
        $application = new GiteeApplication();
        $application->setName('Test Application ' . $uniqueId);
        $application->setClientId('test-client-' . $uniqueId);
        $application->setClientSecret('test-secret-' . $uniqueId);
        self::getEntityManager()->persist($application);
        self::getEntityManager()->flush();

        return $application;
    }

    private function createBaseRepositoryIfNeeded(): void
    {
        // 检查是否已有基础数据
        if (0 === $this->repository->count()) {
            // 创建独立的application用于基础数据
            $application = new GiteeApplication();
            $application->setName('Base Test Application');
            $application->setClientId('base-test-client-id');
            $application->setClientSecret('base-test-secret');
            self::getEntityManager()->persist($application);
            self::getEntityManager()->flush();

            $baseRepository = new GiteeRepository();
            $baseRepository->setApplication($application);
            $baseRepository->setUserId('base-test-user');
            $baseRepository->setFullName('owner/base-test-repo');
            $baseRepository->setName('base-test-repo');
            $baseRepository->setOwner('owner');
            $baseRepository->setDescription('Base test repository');
            $baseRepository->setDefaultBranch('main');
            $baseRepository->setPrivate(false);
            $baseRepository->setFork(false);
            $baseRepository->setHtmlUrl('https://gitee.com/owner/base-test-repo');
            $baseRepository->setSshUrl('git@gitee.com:owner/base-test-repo.git');
            $baseRepository->setPushTime(new \DateTimeImmutable());

            $this->repository->save($baseRepository);
        }
    }

    protected function createNewEntity(): object
    {
        $uniqueId = uniqid();

        // 每个测试创建独立的application
        $application = new GiteeApplication();
        $application->setName('Test App ' . $uniqueId);
        $application->setClientId('test-client-' . $uniqueId);
        $application->setClientSecret('test-secret-' . $uniqueId);
        self::getEntityManager()->persist($application);
        self::getEntityManager()->flush();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('test-user-' . $uniqueId);
        $repository->setFullName('owner/test-repo-' . $uniqueId);
        $repository->setName('test-repo-' . $uniqueId);
        $repository->setOwner('owner' . $uniqueId);
        $repository->setDescription('Test repository description');
        $repository->setDefaultBranch('main');
        $repository->setPrivate(false);
        $repository->setFork(false);
        $repository->setHtmlUrl('https://gitee.com/owner/test-repo-' . $uniqueId);
        $repository->setSshUrl('git@gitee.com:owner/test-repo-' . $uniqueId . '.git');
        $repository->setPushTime(new \DateTimeImmutable());

        return $repository;
    }

    public function testSaveAndRemoveRepository(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('test-user-123');
        $repository->setFullName('owner/test-repo');
        $repository->setName('test-repo');
        $repository->setOwner('owner');
        $repository->setDescription('Test repository');
        $repository->setDefaultBranch('main');
        $repository->setPrivate(false);
        $repository->setFork(false);
        $repository->setHtmlUrl('https://gitee.com/owner/test-repo');
        $repository->setSshUrl('git@gitee.com:owner/test-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());

        $this->repository->save($repository);
        $savedId = $repository->getId();
        $this->assertNotNull($savedId);
        $this->assertGreaterThan(0, $savedId);

        $foundRepository = $this->repository->find($savedId);
        $this->assertInstanceOf(GiteeRepository::class, $foundRepository);
        $this->assertEquals('owner/test-repo', $foundRepository->getFullName());

        $this->repository->remove($repository);
        $removedRepository = $this->repository->find($savedId);
        $this->assertNull($removedRepository);
    }

    public function testSaveWithoutFlush(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('test-user-456');
        $repository->setFullName('owner/no-flush-repo');
        $repository->setName('no-flush-repo');
        $repository->setOwner('owner');
        $repository->setHtmlUrl('https://gitee.com/owner/no-flush-repo');
        $repository->setSshUrl('git@gitee.com:owner/no-flush-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());

        $this->repository->save($repository, false);
        self::getEntityManager()->flush();

        $savedId = $repository->getId();
        $this->assertNotNull($savedId);
        $this->assertGreaterThan(0, $savedId);

        $foundRepository = $this->repository->find($savedId);
        $this->assertInstanceOf(GiteeRepository::class, $foundRepository);
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

    public function testFindOneByWithOrderByForName(): void
    {
        $application = $this->createTestApplication();

        $repository1 = new GiteeRepository();
        $repository1->setApplication($application);
        $repository1->setUserId('name-order-user');
        $repository1->setFullName('owner/aaa-repo');
        $repository1->setName('aaa-repo');
        $repository1->setOwner('owner');
        $repository1->setHtmlUrl('https://gitee.com/owner/aaa-repo');
        $repository1->setSshUrl('git@gitee.com:owner/aaa-repo.git');
        $repository1->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository1);

        $repository2 = new GiteeRepository();
        $repository2->setApplication($application);
        $repository2->setUserId('name-order-user');
        $repository2->setFullName('owner/zzz-repo');
        $repository2->setName('zzz-repo');
        $repository2->setOwner('owner');
        $repository2->setHtmlUrl('https://gitee.com/owner/zzz-repo');
        $repository2->setSshUrl('git@gitee.com:owner/zzz-repo.git');
        $repository2->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository2);

        $result = $this->repository->findOneBy(['userId' => 'name-order-user'], ['name' => 'DESC']);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertEquals('zzz-repo', $result->getName());
    }

    public function testFindWithZeroValueShouldReturnNull(): void
    {
        $result = $this->repository->find(0);
        $this->assertNull($result);
    }

    public function testFindByWithNonMatchingUserIdShouldReturnEmptyArray(): void
    {
        $results = $this->repository->findBy(['userId' => 'non-existent-user']);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testFindOneByWithNonExistentUserIdShouldReturnNull(): void
    {
        $result = $this->repository->findOneBy(['userId' => 'non-existent-user']);
        $this->assertNull($result);
    }

    public function testFindOneByShouldRespectOrderByClause(): void
    {
        $application = $this->createTestApplication();

        $repository1 = new GiteeRepository();
        $repository1->setApplication($application);
        $repository1->setUserId('order-one-user');
        $repository1->setFullName('owner/order-repo-a');
        $repository1->setName('order-repo-a');
        $repository1->setOwner('owner');
        $repository1->setHtmlUrl('https://gitee.com/owner/order-repo-a');
        $repository1->setSshUrl('git@gitee.com:owner/order-repo-a.git');
        $repository1->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository1);

        $repository2 = new GiteeRepository();
        $repository2->setApplication($application);
        $repository2->setUserId('order-one-user');
        $repository2->setFullName('owner/order-repo-z');
        $repository2->setName('order-repo-z');
        $repository2->setOwner('owner');
        $repository2->setHtmlUrl('https://gitee.com/owner/order-repo-z');
        $repository2->setSshUrl('git@gitee.com:owner/order-repo-z.git');
        $repository2->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository2);

        $result = $this->repository->findOneBy(['userId' => 'order-one-user'], ['name' => 'DESC']);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertEquals('order-repo-z', $result->getName());
    }

    public function testQueryWithApplicationAssociation(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('association-user');
        $repository->setFullName('owner/association-repo');
        $repository->setName('association-repo');
        $repository->setOwner('owner');
        $repository->setHtmlUrl('https://gitee.com/owner/association-repo');
        $repository->setSshUrl('git@gitee.com:owner/association-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $results = $this->repository->findBy(['application' => $application]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertEquals($application->getId(), $results[0]->getApplication()->getId());
    }

    public function testCountWithApplicationAssociation(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('count-app-user');
        $repository->setFullName('owner/count-app-repo');
        $repository->setName('count-app-repo');
        $repository->setOwner('owner');
        $repository->setHtmlUrl('https://gitee.com/owner/count-app-repo');
        $repository->setSshUrl('git@gitee.com:owner/count-app-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $count = $this->repository->count(['application' => $application]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindByWithNullDescription(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('null-desc-user');
        $repository->setFullName('owner/null-desc-repo');
        $repository->setName('null-desc-repo');
        $repository->setOwner('owner');
        $repository->setDescription(null);
        $repository->setHtmlUrl('https://gitee.com/owner/null-desc-repo');
        $repository->setSshUrl('git@gitee.com:owner/null-desc-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $results = $this->repository->findBy(['description' => null]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertNull($results[0]->getDescription());
    }

    public function testCountWithNullDescription(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('count-null-desc-user');
        $repository->setFullName('owner/count-null-desc-repo');
        $repository->setName('count-null-desc-repo');
        $repository->setOwner('owner');
        $repository->setDescription(null);
        $repository->setHtmlUrl('https://gitee.com/owner/count-null-desc-repo');
        $repository->setSshUrl('git@gitee.com:owner/count-null-desc-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $count = $this->repository->count(['description' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testRemoveMethod(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('remove-test-user');
        $repository->setFullName('owner/remove-test-repo');
        $repository->setName('remove-test-repo');
        $repository->setOwner('owner');
        $repository->setHtmlUrl('https://gitee.com/owner/remove-test-repo');
        $repository->setSshUrl('git@gitee.com:owner/remove-test-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $savedId = $repository->getId();
        $this->assertGreaterThan(0, $savedId);

        $this->repository->remove($repository);
        $removedRepo = $this->repository->find($savedId);
        $this->assertNull($removedRepo);
    }

    public function testFindByUserAndApplication(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('custom-method-user');
        $repository->setFullName('owner/custom-method-repo');
        $repository->setName('custom-method-repo');
        $repository->setOwner('owner');
        $repository->setHtmlUrl('https://gitee.com/owner/custom-method-repo');
        $repository->setSshUrl('git@gitee.com:owner/custom-method-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $result = $this->repository->findByUserAndApplication('custom-method-user', (string) $application->getId());
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals('custom-method-user', $result[0]->getUserId());
    }

    public function testFindByUserAndApplicationWithNonExistentUser(): void
    {
        $application = $this->createTestApplication();

        $result = $this->repository->findByUserAndApplication('non-existent-user', (string) $application->getId());
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindOneByWithOrderByForUserId(): void
    {
        $application = $this->createTestApplication();

        $repository1 = new GiteeRepository();
        $repository1->setApplication($application);
        $repository1->setUserId('user-aaa');
        $repository1->setFullName('owner/order-repo-1');
        $repository1->setName('order-repo-1');
        $repository1->setOwner('owner');
        $repository1->setHtmlUrl('https://gitee.com/owner/order-repo-1');
        $repository1->setSshUrl('git@gitee.com:owner/order-repo-1.git');
        $repository1->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository1);

        $repository2 = new GiteeRepository();
        $repository2->setApplication($application);
        $repository2->setUserId('user-zzz');
        $repository2->setFullName('owner/order-repo-2');
        $repository2->setName('order-repo-2');
        $repository2->setOwner('owner');
        $repository2->setHtmlUrl('https://gitee.com/owner/order-repo-2');
        $repository2->setSshUrl('git@gitee.com:owner/order-repo-2.git');
        $repository2->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository2);

        // 使用查询构建器过滤掉基础测试数据
        $qb = $this->repository->createQueryBuilder('r')
            ->where('r.userId LIKE :userId')
            ->setParameter('userId', 'user-%')
            ->orderBy('r.userId', 'ASC')
            ->setMaxResults(1)
        ;

        $result = $qb->getQuery()->getOneOrNullResult();
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertEquals('user-aaa', $result->getUserId());
    }

    public function testFindOneByWithOrderByForFullName(): void
    {
        $application = $this->createTestApplication();

        $repository1 = new GiteeRepository();
        $repository1->setApplication($application);
        $repository1->setUserId('fullname-order-user');
        $repository1->setFullName('owner/aaa-repo');
        $repository1->setName('aaa-repo');
        $repository1->setOwner('owner');
        $repository1->setHtmlUrl('https://gitee.com/owner/aaa-repo');
        $repository1->setSshUrl('git@gitee.com:owner/aaa-repo.git');
        $repository1->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository1);

        $repository2 = new GiteeRepository();
        $repository2->setApplication($application);
        $repository2->setUserId('fullname-order-user');
        $repository2->setFullName('owner/zzz-repo');
        $repository2->setName('zzz-repo');
        $repository2->setOwner('owner');
        $repository2->setHtmlUrl('https://gitee.com/owner/zzz-repo');
        $repository2->setSshUrl('git@gitee.com:owner/zzz-repo.git');
        $repository2->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository2);

        $result = $this->repository->findOneBy(['userId' => 'fullname-order-user'], ['fullName' => 'DESC']);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertEquals('owner/zzz-repo', $result->getFullName());
    }

    public function testFindOneByWithOrderByForOwner(): void
    {
        $application = $this->createTestApplication();

        $repository1 = new GiteeRepository();
        $repository1->setApplication($application);
        $repository1->setUserId('owner-order-user');
        $repository1->setFullName('aaa-owner/test-repo');
        $repository1->setName('test-repo');
        $repository1->setOwner('aaa-owner');
        $repository1->setHtmlUrl('https://gitee.com/aaa-owner/test-repo');
        $repository1->setSshUrl('git@gitee.com:aaa-owner/test-repo.git');
        $repository1->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository1);

        $repository2 = new GiteeRepository();
        $repository2->setApplication($application);
        $repository2->setUserId('owner-order-user');
        $repository2->setFullName('zzz-owner/test-repo');
        $repository2->setName('test-repo');
        $repository2->setOwner('zzz-owner');
        $repository2->setHtmlUrl('https://gitee.com/zzz-owner/test-repo');
        $repository2->setSshUrl('git@gitee.com:zzz-owner/test-repo.git');
        $repository2->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository2);

        $result = $this->repository->findOneBy(['userId' => 'owner-order-user'], ['owner' => 'ASC']);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertEquals('aaa-owner', $result->getOwner());
    }

    public function testFindOneByWithOrderByForDefaultBranch(): void
    {
        $application = $this->createTestApplication();

        $repository1 = new GiteeRepository();
        $repository1->setApplication($application);
        $repository1->setUserId('branch-order-user');
        $repository1->setFullName('owner/branch-repo-1');
        $repository1->setName('branch-repo-1');
        $repository1->setOwner('owner');
        $repository1->setDefaultBranch('develop');
        $repository1->setHtmlUrl('https://gitee.com/owner/branch-repo-1');
        $repository1->setSshUrl('git@gitee.com:owner/branch-repo-1.git');
        $repository1->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository1);

        $repository2 = new GiteeRepository();
        $repository2->setApplication($application);
        $repository2->setUserId('branch-order-user');
        $repository2->setFullName('owner/branch-repo-2');
        $repository2->setName('branch-repo-2');
        $repository2->setOwner('owner');
        $repository2->setDefaultBranch('main');
        $repository2->setHtmlUrl('https://gitee.com/owner/branch-repo-2');
        $repository2->setSshUrl('git@gitee.com:owner/branch-repo-2.git');
        $repository2->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository2);

        $result = $this->repository->findOneBy(['userId' => 'branch-order-user'], ['defaultBranch' => 'DESC']);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertEquals('main', $result->getDefaultBranch());
    }

    public function testFindByWithApplicationAssociationById(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('app-id-user');
        $repository->setFullName('owner/app-id-repo');
        $repository->setName('app-id-repo');
        $repository->setOwner('owner');
        $repository->setHtmlUrl('https://gitee.com/owner/app-id-repo');
        $repository->setSshUrl('git@gitee.com:owner/app-id-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $results = $this->repository->findBy(['application' => $application->getId()]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertEquals($application->getId(), $results[0]->getApplication()->getId());
    }

    public function testCountWithApplicationAssociationById(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('count-app-id-user');
        $repository->setFullName('owner/count-app-id-repo');
        $repository->setName('count-app-id-repo');
        $repository->setOwner('owner');
        $repository->setHtmlUrl('https://gitee.com/owner/count-app-id-repo');
        $repository->setSshUrl('git@gitee.com:owner/count-app-id-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $count = $this->repository->count(['application' => $application->getId()]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindOneByWithApplicationAssociation(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('find-one-app-user');
        $repository->setFullName('owner/find-one-app-repo');
        $repository->setName('find-one-app-repo');
        $repository->setOwner('owner');
        $repository->setHtmlUrl('https://gitee.com/owner/find-one-app-repo');
        $repository->setSshUrl('git@gitee.com:owner/find-one-app-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $result = $this->repository->findOneBy(['application' => $application]);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertEquals($application->getId(), $result->getApplication()->getId());
    }

    public function testFindOneByWithNullDescription(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('find-one-null-desc-user');
        $repository->setFullName('owner/find-one-null-desc-repo');
        $repository->setName('find-one-null-desc-repo');
        $repository->setOwner('owner');
        $repository->setDescription(null);
        $repository->setHtmlUrl('https://gitee.com/owner/find-one-null-desc-repo');
        $repository->setSshUrl('git@gitee.com:owner/find-one-null-desc-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $result = $this->repository->findOneBy(['description' => null]);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertNull($result->getDescription());
    }

    public function testCountWithNullDescriptionIsNull(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('count-null-desc-is-null-user');
        $repository->setFullName('owner/count-null-desc-is-null-repo');
        $repository->setName('count-null-desc-is-null-repo');
        $repository->setOwner('owner');
        $repository->setDescription(null);
        $repository->setHtmlUrl('https://gitee.com/owner/count-null-desc-is-null-repo');
        $repository->setSshUrl('git@gitee.com:owner/count-null-desc-is-null-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $count = $this->repository->count(['description' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindWithNullParameterReturnsBehavior(): void
    {
        $this->expectException(MissingIdentifierField::class);
        $this->repository->find(null);
    }

    public function testFindOneByWithOrderByForPrivateField(): void
    {
        $application = $this->createTestApplication();

        $repository1 = new GiteeRepository();
        $repository1->setApplication($application);
        $repository1->setUserId('private-order-user');
        $repository1->setFullName('owner/private-repo-1');
        $repository1->setName('private-repo-1');
        $repository1->setOwner('owner');
        $repository1->setPrivate(false);
        $repository1->setHtmlUrl('https://gitee.com/owner/private-repo-1');
        $repository1->setSshUrl('git@gitee.com:owner/private-repo-1.git');
        $repository1->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository1);

        $repository2 = new GiteeRepository();
        $repository2->setApplication($application);
        $repository2->setUserId('private-order-user');
        $repository2->setFullName('owner/private-repo-2');
        $repository2->setName('private-repo-2');
        $repository2->setOwner('owner');
        $repository2->setPrivate(true);
        $repository2->setHtmlUrl('https://gitee.com/owner/private-repo-2');
        $repository2->setSshUrl('git@gitee.com:owner/private-repo-2.git');
        $repository2->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository2);

        $result = $this->repository->findOneBy(['userId' => 'private-order-user'], ['private' => 'DESC']);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertTrue($result->isPrivate());
    }

    public function testFindOneByWithOrderByForForkField(): void
    {
        $application = $this->createTestApplication();

        $repository1 = new GiteeRepository();
        $repository1->setApplication($application);
        $repository1->setUserId('fork-order-user');
        $repository1->setFullName('owner/fork-repo-1');
        $repository1->setName('fork-repo-1');
        $repository1->setOwner('owner');
        $repository1->setFork(false);
        $repository1->setHtmlUrl('https://gitee.com/owner/fork-repo-1');
        $repository1->setSshUrl('git@gitee.com:owner/fork-repo-1.git');
        $repository1->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository1);

        $repository2 = new GiteeRepository();
        $repository2->setApplication($application);
        $repository2->setUserId('fork-order-user');
        $repository2->setFullName('owner/fork-repo-2');
        $repository2->setName('fork-repo-2');
        $repository2->setOwner('owner');
        $repository2->setFork(true);
        $repository2->setHtmlUrl('https://gitee.com/owner/fork-repo-2');
        $repository2->setSshUrl('git@gitee.com:owner/fork-repo-2.git');
        $repository2->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository2);

        $result = $this->repository->findOneBy(['userId' => 'fork-order-user'], ['fork' => 'ASC']);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertFalse($result->isFork());
    }

    public function testFindOneByWithOrderByForHtmlUrl(): void
    {
        $application = $this->createTestApplication();

        $repository1 = new GiteeRepository();
        $repository1->setApplication($application);
        $repository1->setUserId('html-url-order-user');
        $repository1->setFullName('owner/html-url-repo-1');
        $repository1->setName('html-url-repo-1');
        $repository1->setOwner('owner');
        $repository1->setHtmlUrl('https://gitee.com/aaa/html-url-repo-1');
        $repository1->setSshUrl('git@gitee.com:aaa/html-url-repo-1.git');
        $repository1->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository1);

        $repository2 = new GiteeRepository();
        $repository2->setApplication($application);
        $repository2->setUserId('html-url-order-user');
        $repository2->setFullName('owner/html-url-repo-2');
        $repository2->setName('html-url-repo-2');
        $repository2->setOwner('owner');
        $repository2->setHtmlUrl('https://gitee.com/zzz/html-url-repo-2');
        $repository2->setSshUrl('git@gitee.com:zzz/html-url-repo-2.git');
        $repository2->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository2);

        $result = $this->repository->findOneBy(['userId' => 'html-url-order-user'], ['htmlUrl' => 'DESC']);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertEquals('https://gitee.com/zzz/html-url-repo-2', $result->getHtmlUrl());
    }

    public function testFindOneByWithOrderByForSshUrl(): void
    {
        $application = $this->createTestApplication();

        $repository1 = new GiteeRepository();
        $repository1->setApplication($application);
        $repository1->setUserId('ssh-url-order-user');
        $repository1->setFullName('owner/ssh-url-repo-1');
        $repository1->setName('ssh-url-repo-1');
        $repository1->setOwner('owner');
        $repository1->setHtmlUrl('https://gitee.com/owner/ssh-url-repo-1');
        $repository1->setSshUrl('git@gitee.com:aaa/ssh-url-repo-1.git');
        $repository1->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository1);

        $repository2 = new GiteeRepository();
        $repository2->setApplication($application);
        $repository2->setUserId('ssh-url-order-user');
        $repository2->setFullName('owner/ssh-url-repo-2');
        $repository2->setName('ssh-url-repo-2');
        $repository2->setOwner('owner');
        $repository2->setHtmlUrl('https://gitee.com/owner/ssh-url-repo-2');
        $repository2->setSshUrl('git@gitee.com:zzz/ssh-url-repo-2.git');
        $repository2->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository2);

        $result = $this->repository->findOneBy(['userId' => 'ssh-url-order-user'], ['sshUrl' => 'ASC']);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertEquals('git@gitee.com:aaa/ssh-url-repo-1.git', $result->getSshUrl());
    }

    public function testFindOneByWithOrderByForPushedAt(): void
    {
        $application = $this->createTestApplication();

        $olderDate = new \DateTimeImmutable('2023-01-01');
        $newerDate = new \DateTimeImmutable('2023-12-31');

        $repository1 = new GiteeRepository();
        $repository1->setApplication($application);
        $repository1->setUserId('pushed-at-order-user');
        $repository1->setFullName('owner/pushed-at-repo-1');
        $repository1->setName('pushed-at-repo-1');
        $repository1->setOwner('owner');
        $repository1->setHtmlUrl('https://gitee.com/owner/pushed-at-repo-1');
        $repository1->setSshUrl('git@gitee.com:owner/pushed-at-repo-1.git');
        $repository1->setPushTime($olderDate);
        $this->repository->save($repository1);

        $repository2 = new GiteeRepository();
        $repository2->setApplication($application);
        $repository2->setUserId('pushed-at-order-user');
        $repository2->setFullName('owner/pushed-at-repo-2');
        $repository2->setName('pushed-at-repo-2');
        $repository2->setOwner('owner');
        $repository2->setHtmlUrl('https://gitee.com/owner/pushed-at-repo-2');
        $repository2->setSshUrl('git@gitee.com:owner/pushed-at-repo-2.git');
        $repository2->setPushTime($newerDate);
        $this->repository->save($repository2);

        $result = $this->repository->findOneBy(['userId' => 'pushed-at-order-user'], ['pushTime' => 'DESC']);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertEquals($newerDate, $result->getPushTime());
    }

    public function testFindOneByWithOrderByForDescription(): void
    {
        $application = $this->createTestApplication();

        $repository1 = new GiteeRepository();
        $repository1->setApplication($application);
        $repository1->setUserId('desc-order-user');
        $repository1->setFullName('owner/desc-repo-1');
        $repository1->setName('desc-repo-1');
        $repository1->setOwner('owner');
        $repository1->setDescription('AAA Description');
        $repository1->setHtmlUrl('https://gitee.com/owner/desc-repo-1');
        $repository1->setSshUrl('git@gitee.com:owner/desc-repo-1.git');
        $repository1->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository1);

        $repository2 = new GiteeRepository();
        $repository2->setApplication($application);
        $repository2->setUserId('desc-order-user');
        $repository2->setFullName('owner/desc-repo-2');
        $repository2->setName('desc-repo-2');
        $repository2->setOwner('owner');
        $repository2->setDescription('ZZZ Description');
        $repository2->setHtmlUrl('https://gitee.com/owner/desc-repo-2');
        $repository2->setSshUrl('git@gitee.com:owner/desc-repo-2.git');
        $repository2->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository2);

        $result = $this->repository->findOneBy(['userId' => 'desc-order-user'], ['description' => 'ASC']);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertEquals('AAA Description', $result->getDescription());
    }

    public function testFindOneByWithOrderByForId(): void
    {
        $application = $this->createTestApplication();

        $repository1 = new GiteeRepository();
        $repository1->setApplication($application);
        $repository1->setUserId('id-order-user');
        $repository1->setFullName('owner/id-repo-1');
        $repository1->setName('id-repo-1');
        $repository1->setOwner('owner');
        $repository1->setHtmlUrl('https://gitee.com/owner/id-repo-1');
        $repository1->setSshUrl('git@gitee.com:owner/id-repo-1.git');
        $repository1->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository1);

        $repository2 = new GiteeRepository();
        $repository2->setApplication($application);
        $repository2->setUserId('id-order-user');
        $repository2->setFullName('owner/id-repo-2');
        $repository2->setName('id-repo-2');
        $repository2->setOwner('owner');
        $repository2->setHtmlUrl('https://gitee.com/owner/id-repo-2');
        $repository2->setSshUrl('git@gitee.com:owner/id-repo-2.git');
        $repository2->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository2);

        $result = $this->repository->findOneBy(['userId' => 'id-order-user'], ['id' => 'ASC']);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertLessThan($repository2->getId(), $result->getId());
    }

    public function testFindByWithApplicationIdAsString(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('app-string-user');
        $repository->setFullName('owner/app-string-repo');
        $repository->setName('app-string-repo');
        $repository->setOwner('owner');
        $repository->setHtmlUrl('https://gitee.com/owner/app-string-repo');
        $repository->setSshUrl('git@gitee.com:owner/app-string-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $results = $this->repository->findBy(['application' => (string) $application->getId()]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertEquals($application->getId(), $results[0]->getApplication()->getId());
    }

    public function testCountWithApplicationIdAsString(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('count-app-string-user');
        $repository->setFullName('owner/count-app-string-repo');
        $repository->setName('count-app-string-repo');
        $repository->setOwner('owner');
        $repository->setHtmlUrl('https://gitee.com/owner/count-app-string-repo');
        $repository->setSshUrl('git@gitee.com:owner/count-app-string-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $count = $this->repository->count(['application' => (string) $application->getId()]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindOneByWithApplicationIdAsString(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('find-one-app-string-user');
        $repository->setFullName('owner/find-one-app-string-repo');
        $repository->setName('find-one-app-string-repo');
        $repository->setOwner('owner');
        $repository->setHtmlUrl('https://gitee.com/owner/find-one-app-string-repo');
        $repository->setSshUrl('git@gitee.com:owner/find-one-app-string-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $result = $this->repository->findOneBy(['application' => (string) $application->getId()]);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertEquals($application->getId(), $result->getApplication()->getId());
    }

    public function testFindOneByAssociationApplicationShouldReturnMatchingEntity(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('assoc-app-match-user');
        $repository->setFullName('owner/assoc-app-match-repo');
        $repository->setName('assoc-app-match-repo');
        $repository->setOwner('owner');
        $repository->setHtmlUrl('https://gitee.com/owner/assoc-app-match-repo');
        $repository->setSshUrl('git@gitee.com:owner/assoc-app-match-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $result = $this->repository->findOneBy(['application' => $application]);
        $this->assertInstanceOf(GiteeRepository::class, $result);
        $this->assertEquals($application->getId(), $result->getApplication()->getId());
    }

    public function testCountWithNullDescriptionAsNullShouldReturnCorrectCount(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('count-null-desc-user');
        $repository->setFullName('owner/count-null-desc-repo');
        $repository->setName('count-null-desc-repo');
        $repository->setOwner('owner');
        $repository->setDescription(null);
        $repository->setHtmlUrl('https://gitee.com/owner/count-null-desc-repo');
        $repository->setSshUrl('git@gitee.com:owner/count-null-desc-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $count = $this->repository->count(['description' => null]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testCountWithAssociationApplicationShouldReturnCorrectCount(): void
    {
        $application = $this->createTestApplication();

        $repository = new GiteeRepository();
        $repository->setApplication($application);
        $repository->setUserId('count-assoc-app-user');
        $repository->setFullName('owner/count-assoc-app-repo');
        $repository->setName('count-assoc-app-repo');
        $repository->setOwner('owner');
        $repository->setHtmlUrl('https://gitee.com/owner/count-assoc-app-repo');
        $repository->setSshUrl('git@gitee.com:owner/count-assoc-app-repo.git');
        $repository->setPushTime(new \DateTimeImmutable());
        $this->repository->save($repository);

        $count = $this->repository->count(['application' => $application]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testCountByAssociationApplicationShouldReturnCorrectNumber(): void
    {
        $application = $this->createTestApplication();

        for ($i = 0; $i < 3; ++$i) {
            $repository = new GiteeRepository();
            $repository->setApplication($application);
            $repository->setUserId('count-by-assoc-app-user-' . $i);
            $repository->setFullName('owner/count-by-assoc-app-repo-' . $i);
            $repository->setName('count-by-assoc-app-repo-' . $i);
            $repository->setOwner('owner');
            $repository->setHtmlUrl('https://gitee.com/owner/count-by-assoc-app-repo-' . $i);
            $repository->setSshUrl('git@gitee.com:owner/count-by-assoc-app-repo-' . $i . '.git');
            $repository->setPushTime(new \DateTimeImmutable());
            $this->repository->save($repository);
        }

        $count = $this->repository->count(['application' => $application]);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(3, $count);
    }

    /**
     * @return ServiceEntityRepository<GiteeRepository>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        // 如果不是数据库连接测试，才创建基础数据
        if (!$this->isTestingDatabaseConnection()) {
            $this->createBaseRepositoryIfNeeded();
        }

        return $this->repository;
    }
}
