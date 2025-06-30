<?php

namespace GiteeApiBundle\Tests\Integration\Command;

use Doctrine\ORM\EntityManagerInterface;
use GiteeApiBundle\Command\SyncRepositoriesCommand;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Repository\GiteeApplicationRepository;
use GiteeApiBundle\Repository\GiteeRepositoryRepository;
use GiteeApiBundle\Service\GiteeRepositoryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class SyncRepositoriesCommandTest extends TestCase
{
    private SyncRepositoriesCommand $command;
    private MockObject $entityManager;
    private MockObject $repositoryService;
    private MockObject $applicationRepository;
    private MockObject $repositoryRepository;
    
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repositoryService = $this->createMock(GiteeRepositoryService::class);
        $this->applicationRepository = $this->createMock(GiteeApplicationRepository::class);
        $this->repositoryRepository = $this->createMock(GiteeRepositoryRepository::class);
        
        $this->command = new SyncRepositoriesCommand(
            $this->entityManager,
            $this->repositoryService,
            $this->applicationRepository,
            $this->repositoryRepository
        );
    }
    
    public function testCommandName(): void
    {
        $this->assertEquals('gitee:sync:repositories', $this->command::NAME);
        $this->assertEquals('gitee:sync:repositories', $this->command->getName());
    }
    
    public function testCommandExecution_applicationNotFound(): void
    {
        $input = new ArrayInput([
            'userId' => 'test_user',
            'applicationId' => '999'
        ]);
        $output = new BufferedOutput();
        
        $this->applicationRepository->expects($this->once())
            ->method('find')
            ->with('999')
            ->willReturn(null);
        
        $result = $this->command->run($input, $output);
        
        $this->assertEquals(Command::FAILURE, $result);
        $this->assertStringContainsString('应用不存在', $output->fetch());
    }
    
    public function testCommandExecution_repositoryServiceError(): void
    {
        $input = new ArrayInput([
            'userId' => 'test_user',
            'applicationId' => '1'
        ]);
        $output = new BufferedOutput();
        
        $application = new GiteeApplication();
        $application->setName('Test App')
            ->setClientId('client_id')
            ->setClientSecret('client_secret');
            
        $this->applicationRepository->expects($this->once())
            ->method('find')
            ->with('1')
            ->willReturn($application);
            
        $this->repositoryService->expects($this->once())
            ->method('getRepositories')
            ->willThrowException(new \Exception('API Error'));
        
        $result = $this->command->run($input, $output);
        
        $this->assertEquals(Command::FAILURE, $result);
        $this->assertStringContainsString('获取仓库列表失败: API Error', $output->fetch());
    }
    
    public function testCommandExecution_success(): void
    {
        $input = new ArrayInput([
            'userId' => 'test_user',
            'applicationId' => '1'
        ]);
        $output = new BufferedOutput();
        
        $application = new GiteeApplication();
        $application->setName('Test App')
            ->setClientId('client_id')
            ->setClientSecret('client_secret');
            
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
                'pushed_at' => '2024-01-01T00:00:00Z'
            ]
        ];
        
        $this->applicationRepository->expects($this->once())
            ->method('find')
            ->with('1')
            ->willReturn($application);
            
        $this->repositoryService->expects($this->once())
            ->method('getRepositories')
            ->with('test_user', $application)
            ->willReturn($repositoryData);
            
        $this->repositoryRepository->expects($this->once())
            ->method('findByUserAndApplication')
            ->with('test_user', '1')
            ->willReturn([]);
            
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(\GiteeApiBundle\Entity\GiteeRepository::class));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $result = $this->command->run($input, $output);
        $outputContent = $output->fetch();
        
        $this->assertEquals(Command::SUCCESS, $result);
        $this->assertStringContainsString('找到 1 个仓库', $outputContent);
        $this->assertStringContainsString('同步完成:', $outputContent);
        $this->assertStringContainsString('处理 1 个仓库', $outputContent);
    }
}