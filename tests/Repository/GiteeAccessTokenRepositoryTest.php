<?php

namespace GiteeApiBundle\Tests\Repository;

use GiteeApiBundle\Entity\GiteeAccessToken;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Repository\GiteeAccessTokenRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(GiteeAccessTokenRepository::class)]
#[RunTestsInSeparateProcesses]
final class GiteeAccessTokenRepositoryTest extends AbstractRepositoryTestCase
{
    private GiteeAccessTokenRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(GiteeAccessTokenRepository::class);

        // 如果当前测试是数据库连接测试，跳过数据加载操作
        if ($this->isTestingDatabaseConnection()) {
            return;
        }

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

    protected function getRepository(): GiteeAccessTokenRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): object
    {
        $accessToken = new GiteeAccessToken();
        $accessToken->setUserId('test-user-' . uniqid());
        $accessToken->setAccessToken('test-token-' . uniqid());
        $accessToken->setRefreshToken('test-refresh-token-' . uniqid());
        $accessToken->setGiteeUsername('testuser-' . uniqid());

        // 只在非数据库连接测试时创建和持久化application
        if (!$this->isTestingDatabaseConnection()) {
            $application = new GiteeApplication();
            $application->setName('Test App');
            $application->setClientId('test-client-' . uniqid());
            $application->setClientSecret('test-client-secret-' . uniqid());
            self::getEntityManager()->persist($application);
            self::getEntityManager()->flush();

            $accessToken->setApplication($application);
        } else {
            // 在数据库连接测试中，创建一个临时application但不持久化
            $application = new GiteeApplication();
            $application->setName('Temp App');
            $application->setClientId('temp-client-' . uniqid());
            $application->setClientSecret('temp-secret');
            $accessToken->setApplication($application);
        }

        return $accessToken;
    }

    public function testFindByUserId(): void
    {
        $application = new GiteeApplication();
        $application->setName('Test Application');
        $application->setClientId('test-client-id');
        $application->setClientSecret('test-client-secret');
        self::getEntityManager()->persist($application);
        self::getEntityManager()->flush();

        $accessToken = new GiteeAccessToken();
        $accessToken->setApplication($application);
        $accessToken->setUserId('test-user');
        $accessToken->setAccessToken('test-token');
        $this->repository->save($accessToken);

        $result = $this->repository->findByUserId('test-user');
        $this->assertInstanceOf(GiteeAccessToken::class, $result);
        $this->assertEquals('test-user', $result->getUserId());
    }

    public function testFindLatestByUserAndApplication(): void
    {
        $application = new GiteeApplication();
        $application->setName('Test Application');
        $application->setClientId('test-client-id');
        $application->setClientSecret('test-client-secret');
        self::getEntityManager()->persist($application);
        self::getEntityManager()->flush();

        $accessToken1 = new GiteeAccessToken();
        $accessToken1->setApplication($application);
        $accessToken1->setUserId('test-user');
        $accessToken1->setAccessToken('token-1');
        $this->repository->save($accessToken1);

        sleep(1);

        $accessToken2 = new GiteeAccessToken();
        $accessToken2->setApplication($application);
        $accessToken2->setUserId('test-user');
        $accessToken2->setAccessToken('token-2');
        $this->repository->save($accessToken2);

        $result = $this->repository->findLatestByUserAndApplication('test-user', (string) $application->getId());
        $this->assertInstanceOf(GiteeAccessToken::class, $result);
        $this->assertEquals('token-2', $result->getAccessToken());
    }
}
