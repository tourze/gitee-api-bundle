<?php

declare(strict_types=1);

namespace GiteeApiBundle\Service;

use GiteeApiBundle\Entity\GiteeAccessToken;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Entity\GiteeRepository;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(private LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('Gitee API管理')) {
            $item->addChild('Gitee API管理');
        }

        $giteeMenu = $item->getChild('Gitee API管理');

        if (null === $giteeMenu) {
            return;
        }

        $giteeMenu
            ->addChild('Gitee应用')
            ->setUri($this->linkGenerator->getCurdListPage(GiteeApplication::class))
            ->setAttribute('icon', 'fas fa-code-branch')
        ;

        $giteeMenu
            ->addChild('访问令牌')
            ->setUri($this->linkGenerator->getCurdListPage(GiteeAccessToken::class))
            ->setAttribute('icon', 'fas fa-key')
        ;

        $giteeMenu
            ->addChild('仓库信息')
            ->setUri($this->linkGenerator->getCurdListPage(GiteeRepository::class))
            ->setAttribute('icon', 'fas fa-folder')
        ;
    }
}
