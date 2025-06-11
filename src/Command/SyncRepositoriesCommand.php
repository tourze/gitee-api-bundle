<?php

namespace GiteeApiBundle\Command;

use DateTimeImmutable;
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

#[AsCommand(
    name: 'gitee:sync:repositories',
    description: '同步用户的Gitee仓库信息',
)]
class SyncRepositoriesCommand extends Command
{
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
            ->addOption('force', 'f', InputOption::VALUE_NONE, '强制更新所有仓库信息');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getArgument('userId');
        $applicationId = $input->getArgument('applicationId');
        $force = $input->getOption('force');

        $application = $this->applicationRepository->find($applicationId);
        if (!$application instanceof GiteeApplication) {
            $io->error('应用不存在');
            return Command::FAILURE;
        }

        // 获取用户的所有仓库
        try {
            $repositories = $this->repositoryService->getRepositories($userId, $application);
        } catch  (\Throwable $e) {
            $io->error(sprintf('获取仓库列表失败: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $io->info(sprintf('找到 %d 个仓库', count($repositories)));

        $existingRepos = [];
        foreach ($this->repositoryRepository->findByUserAndApplication($userId, $applicationId) as $repo) {
            $existingRepos[$repo->getFullName()] = $repo;
        }

        $processed = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($repositories as $repoData) {
            $fullName = $repoData['full_name'];
            $pushedAt = new DateTimeImmutable($repoData['pushed_at']);

            // 检查是否需要更新
            if (!$force && isset($existingRepos[$fullName])) {
                $existingRepo = $existingRepos[$fullName];
                if ($existingRepo->getPushedAt() >= $pushedAt) {
                    $skipped++;
                    continue;
                }
            }

            // 创建或更新仓库信息
            $repo = $existingRepos[$fullName] ?? new GiteeRepository();
            $isNew = !$repo->getId();

            $repo->setApplication($application)
                ->setUserId($userId)
                ->setFullName($fullName)
                ->setName($repoData['name'])
                ->setOwner($repoData['owner']['login'])
                ->setDescription($repoData['description'])
                ->setDefaultBranch($repoData['default_branch'])
                ->setPrivate($repoData['private'])
                ->setFork($repoData['fork'])
                ->setHtmlUrl($repoData['html_url'])
                ->setSshUrl($repoData['ssh_url'])
                ->setPushedAt($pushedAt);

            $this->entityManager->persist($repo);

            if ($isNew) {
                $created++;
            } else {
                $updated++;
            }

            $processed++;

            // 每100个仓库刷新一次
            if ($processed % 100 === 0) {
                $this->entityManager->flush();
                $io->info(sprintf('已处理 %d 个仓库', $processed));
            }
        }

        // 最后刷新一次
        $this->entityManager->flush();

        $io->success(sprintf(
            '同步完成: 处理 %d 个仓库, 新增 %d 个, 更新 %d 个, 跳过 %d 个',
            $processed,
            $created,
            $updated,
            $skipped
        ));

        return Command::SUCCESS;
    }
}
