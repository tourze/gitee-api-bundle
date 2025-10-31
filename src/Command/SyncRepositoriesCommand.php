<?php

declare(strict_types=1);

namespace GiteeApiBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Entity\GiteeRepository;
use GiteeApiBundle\Repository\GiteeApplicationRepository;
use GiteeApiBundle\Repository\GiteeRepositoryRepository;
use GiteeApiBundle\Service\GiteeRepositoryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsCommand(
    name: self::NAME,
    description: '同步用户的Gitee仓库信息',
)]
#[Autoconfigure(public: true)]
class SyncRepositoriesCommand extends Command
{
    public const NAME = 'gitee:sync:repositories';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GiteeRepositoryService $repositoryService,
        private readonly GiteeApplicationRepository $applicationRepository,
        private readonly GiteeRepositoryRepository $repositoryRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userId', InputArgument::REQUIRED, '用户ID')
            ->addArgument('applicationId', InputArgument::REQUIRED, '应用ID')
            ->addOption('force', 'f', InputOption::VALUE_NONE, '强制更新所有仓库信息')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getArgument('userId');
        $applicationId = $input->getArgument('applicationId');
        $force = $input->getOption('force');

        $application = $this->getGiteeApplication($applicationId, $io);
        if (null === $application) {
            return Command::FAILURE;
        }

        $repositories = $this->fetchRepositories($userId, $application, $io);
        if (null === $repositories) {
            return Command::FAILURE;
        }

        $io->info(sprintf('找到 %d 个仓库', count($repositories)));

        $existingRepos = $this->getExistingRepositories($userId, $applicationId);
        $stats = $this->syncRepositories($repositories, $existingRepos, $application, $userId, $force, $io);

        $this->entityManager->flush();

        $io->success(sprintf(
            '同步完成: 处理 %d 个仓库, 新增 %d 个, 更新 %d 个, 跳过 %d 个',
            $stats['processed'],
            $stats['created'],
            $stats['updated'],
            $stats['skipped']
        ));

        return Command::SUCCESS;
    }

    private function getGiteeApplication(mixed $applicationId, SymfonyStyle $io): ?GiteeApplication
    {
        $application = $this->applicationRepository->find($applicationId);
        if (!$application instanceof GiteeApplication) {
            $io->error('应用不存在');

            return null;
        }

        return $application;
    }

    /** @return array<array-key, array<string, mixed>>|null */
    private function fetchRepositories(string $userId, GiteeApplication $application, SymfonyStyle $io): ?array
    {
        try {
            return $this->repositoryService->getRepositories($userId, $application);
        } catch (\Throwable $e) {
            $io->error(sprintf('获取仓库列表失败: %s', $e->getMessage()));

            return null;
        }
    }

    /** @return array<string, GiteeRepository> */
    private function getExistingRepositories(string $userId, mixed $applicationId): array
    {
        $existingRepos = [];
        foreach ($this->repositoryRepository->findByUserAndApplication($userId, $applicationId) as $repo) {
            $existingRepos[$repo->getFullName()] = $repo;
        }

        return $existingRepos;
    }

    /**
     * @param array<array-key, array<string, mixed>> $repositories
     * @param array<string, GiteeRepository> $existingRepos
     * @return array{processed: int, created: int, updated: int, skipped: int}
     */
    private function syncRepositories(
        array $repositories,
        array $existingRepos,
        GiteeApplication $application,
        string $userId,
        bool $force,
        SymfonyStyle $io,
    ): array {
        $stats = ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($repositories as $repoData) {
            if ($this->shouldSkipRepository($repoData, $existingRepos, $force)) {
                ++$stats['skipped'];
                continue;
            }

            $isNew = $this->processRepository($repoData, $existingRepos, $application, $userId);
            $isNew ? ++$stats['created'] : ++$stats['updated'];
            ++$stats['processed'];

            $this->flushPeriodically($stats['processed'], $io);
        }

        return $stats;
    }

    /**
     * @param array<string, mixed> $repoData
     * @param array<string, GiteeRepository> $existingRepos
     */
    private function shouldSkipRepository(array $repoData, array $existingRepos, bool $force): bool
    {
        if ($force) {
            return false;
        }

        $fullName = $repoData['full_name'];
        if (!isset($existingRepos[$fullName])) {
            return false;
        }

        $existingRepo = $existingRepos[$fullName];
        $pushTime = new \DateTimeImmutable($repoData['pushed_at']);

        return $existingRepo->getPushTime() >= $pushTime;
    }

    /**
     * @param array<string, mixed> $repoData
     * @param array<string, GiteeRepository> $existingRepos
     */
    private function processRepository(
        array $repoData,
        array $existingRepos,
        GiteeApplication $application,
        string $userId,
    ): bool {
        $fullName = $repoData['full_name'];
        $repo = $existingRepos[$fullName] ?? new GiteeRepository();
        $isNew = null === $repo->getId();

        $pushTime = new \DateTimeImmutable($repoData['pushed_at']);
        $repo->setApplication($application);
        $repo->setUserId($userId);
        $repo->setFullName($fullName);
        $repo->setName($repoData['name']);
        $repo->setOwner($repoData['owner']['login']);
        $repo->setDescription($repoData['description']);
        $repo->setDefaultBranch($repoData['default_branch']);
        $repo->setPrivate($repoData['private']);
        $repo->setFork($repoData['fork']);
        $repo->setHtmlUrl($repoData['html_url']);
        $repo->setSshUrl($repoData['ssh_url']);
        $repo->setPushTime($pushTime);

        $this->entityManager->persist($repo);

        return $isNew;
    }

    private function flushPeriodically(int $processed, SymfonyStyle $io): void
    {
        if (0 === $processed % 100) {
            $this->entityManager->flush();
            $io->info(sprintf('已处理 %d 个仓库', $processed));
        }
    }
}
