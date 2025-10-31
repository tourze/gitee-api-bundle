<?php

namespace GiteeApiBundle\Tests\Command;

use GiteeApiBundle\Command\SyncRepositoriesCommand;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Repository\GiteeApplicationRepository;
use GiteeApiBundle\Repository\GiteeRepositoryRepository;
use GiteeApiBundle\Service\GiteeRepositoryService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(SyncRepositoriesCommand::class)]
#[RunTestsInSeparateProcesses]
final class SyncRepositoriesCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    private MockObject $repositoryService;

    private MockObject $applicationRepository;

    private MockObject $repositoryRepository;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        // 必须使用具体类 GiteeRepositoryService 的 Mock，原因：
        // 1. GiteeRepositoryService 没有对应的接口或抽象类可供测试
        // 2. 测试需要验证与 getRepositories 方法的具体交互行为
        // 3. 命令行工具测试场景下，需要模拟具体服务实现的业务逻辑
        $this->repositoryService = $this->createMock(GiteeRepositoryService::class);
        // 必须使用具体类 GiteeApplicationRepository 的 Mock，原因：
        // 1. GiteeApplicationRepository 没有对应的接口或抽象类可供测试
        // 2. 测试需要验证与 find 方法的具体交互行为
        // 3. Repository 测试场景下，需要模拟具体仓库实现的业务逻辑
        $this->applicationRepository = $this->createMock(GiteeApplicationRepository::class);
        // 必须使用具体类 GiteeRepositoryRepository 的 Mock，原因：
        // 1. GiteeRepositoryRepository 没有对应的接口或抽象类可供测试
        // 2. 测试需要验证与 findByUserAndApplication 方法的具体交互行为
        // 3. Repository 测试场景下，需要模拟具体仓库实现的业务逻辑
        $this->repositoryRepository = $this->createMock(GiteeRepositoryRepository::class);

        // 将 Mock 对象注册到容器中
        $container = self::getContainer();
        $container->set(GiteeRepositoryService::class, $this->repositoryService);
        $container->set(GiteeApplicationRepository::class, $this->applicationRepository);
        $container->set(GiteeRepositoryRepository::class, $this->repositoryRepository);

        // 设置 CommandTester
        $command = self::getService(SyncRepositoriesCommand::class);
        $this->commandTester = new CommandTester($command);
    }

    public function testCommandName(): void
    {
        $command = self::getService(SyncRepositoriesCommand::class);
        $this->assertEquals('gitee:sync:repositories', SyncRepositoriesCommand::NAME);
        $this->assertEquals('gitee:sync:repositories', $command->getName());
    }

    public function testCommandExecutionWithCommandTester(): void
    {
        $application = new GiteeApplication();
        $application->setName('Test App');
        $application->setClientId('client_id');
        $application->setClientSecret('client_secret');

        $this->applicationRepository->expects($this->once())
            ->method('find')
            ->with('1')
            ->willReturn($application)
        ;

        $this->repositoryService->expects($this->once())
            ->method('getRepositories')
            ->with('test_user', $application)
            ->willReturn([])
        ;

        $this->repositoryRepository->expects($this->once())
            ->method('findByUserAndApplication')
            ->with('test_user', '1')
            ->willReturn([])
        ;

        $this->commandTester->execute([
            'userId' => 'test_user',
            'applicationId' => '1',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('找到 0 个仓库', $this->commandTester->getDisplay());
    }

    public function testCommandExecutionApplicationNotFound(): void
    {
        $this->applicationRepository->expects($this->once())
            ->method('find')
            ->with('999')
            ->willReturn(null)
        ;

        $this->commandTester->execute([
            'userId' => 'test_user',
            'applicationId' => '999',
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('应用不存在', $this->commandTester->getDisplay());
    }

    public function testCommandExecutionRepositoryServiceError(): void
    {
        $application = new GiteeApplication();
        $application->setName('Test App');
        $application->setClientId('client_id');
        $application->setClientSecret('client_secret');

        $this->applicationRepository->expects($this->once())
            ->method('find')
            ->with('1')
            ->willReturn($application)
        ;

        $this->repositoryService->expects($this->once())
            ->method('getRepositories')
            ->willThrowException(new \Exception('API Error'))
        ;

        $this->commandTester->execute([
            'userId' => 'test_user',
            'applicationId' => '1',
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('获取仓库列表失败: API Error', $this->commandTester->getDisplay());
    }

    public function testCommandExecutionSuccess(): void
    {
        $application = new GiteeApplication();
        $application->setName('Test App');
        $application->setClientId('client_id');
        $application->setClientSecret('client_secret');

        // 在命令测试中需要持久化实体
        $this->persistAndFlush($application);

        $repositoryData = [
            [
                'full_name' => 'test_user/test_repo',
                'name' => 'test_repo',
                'owner' => ['login' => 'test_user'],
                'description' => 'Test repository',
                'default_branch' => 'main',
                'private' => false,
                'fork' => false,
                'html_url' => 'https://gitee.com/test_user/test_repo',
                'ssh_url' => 'git@gitee.com:test_user/test_repo.git',
                'pushed_at' => '2024-01-01T00:00:00Z',
            ],
        ];

        $this->applicationRepository->expects($this->once())
            ->method('find')
            ->with('1')
            ->willReturn($application)
        ;

        $this->repositoryService->expects($this->once())
            ->method('getRepositories')
            ->with('test_user', $application)
            ->willReturn($repositoryData)
        ;

        $this->repositoryRepository->expects($this->once())
            ->method('findByUserAndApplication')
            ->with('test_user', self::anything())
            ->willReturn([])
        ;

        $this->commandTester->execute([
            'userId' => 'test_user',
            'applicationId' => '1',
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $outputContent = $this->commandTester->getDisplay();
        $this->assertStringContainsString('找到 1 个仓库', $outputContent);
        $this->assertStringContainsString('同步完成:', $outputContent);
        $this->assertStringContainsString('处理 1 个仓库', $outputContent);
    }

    public function testArgumentUserId(): void
    {
        $this->applicationRepository->expects($this->once())
            ->method('find')
            ->with('1')
            ->willReturn(null)
        ;

        $this->commandTester->execute([
            'userId' => 'test-user-id',
            'applicationId' => '1',
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('应用不存在', $this->commandTester->getDisplay());
    }

    public function testArgumentApplicationId(): void
    {
        $this->applicationRepository->expects($this->once())
            ->method('find')
            ->with('test-app-id')
            ->willReturn(null)
        ;

        $this->commandTester->execute([
            'userId' => 'test-user',
            'applicationId' => 'test-app-id',
        ]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('应用不存在', $this->commandTester->getDisplay());
    }

    public function testOptionForce(): void
    {
        $application = new GiteeApplication();
        $application->setName('Test App');
        $application->setClientId('client_id');
        $application->setClientSecret('client_secret');

        $this->applicationRepository->expects($this->once())
            ->method('find')
            ->with('1')
            ->willReturn($application)
        ;

        $this->repositoryService->expects($this->once())
            ->method('getRepositories')
            ->with('test_user', $application)
            ->willReturn([])
        ;

        $this->repositoryRepository->expects($this->once())
            ->method('findByUserAndApplication')
            ->with('test_user', '1')
            ->willReturn([])
        ;

        $this->commandTester->execute([
            'userId' => 'test_user',
            'applicationId' => '1',
            '--force' => true,
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('找到 0 个仓库', $this->commandTester->getDisplay());
    }
}
