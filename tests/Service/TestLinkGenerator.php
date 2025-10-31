<?php

declare(strict_types=1);

namespace GiteeApiBundle\Tests\Service;

use GiteeApiBundle\Entity\GiteeAccessToken;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Entity\GiteeRepository;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;

/**
 * 测试用的 LinkGenerator 实现
 */
class TestLinkGenerator implements LinkGeneratorInterface
{
    public function getCurdListPage(string $entityClass): string
    {
        return match ($entityClass) {
            GiteeApplication::class => '/admin/gitee-application',
            GiteeAccessToken::class => '/admin/gitee-access-token',
            GiteeRepository::class => '/admin/gitee-repository',
            default => '',
        };
    }

    public function extractEntityFqcn(string $url): ?string
    {
        return null;
    }

    public function setDashboard(string $dashboardControllerFqcn): void
    {
        // 测试类空实现，满足接口契约即可
    }
}
