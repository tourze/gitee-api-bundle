<?php

namespace GiteeApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use GiteeApiBundle\Repository\GiteeRepositoryRepository;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;

#[ORM\Entity(repositoryClass: GiteeRepositoryRepository::class)]
#[ORM\Table(name: 'gitee_repository', options: ['comment' => 'Gitee仓库信息'])]
#[ORM\UniqueConstraint(columns: ['user_id', 'application_id', 'full_name'])]
class GiteeRepository
{
    use TimestampableAware;
    #[ListColumn(order: -1)]
    #[ExportColumn]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = 0;

    #[ORM\ManyToOne(targetEntity: GiteeApplication::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GiteeApplication $application;

    #[ORM\Column(type: 'string', length: 255, options: ['comment' => '用户ID'])]
    private string $userId;

    #[ORM\Column(type: 'string', length: 255, options: ['comment' => '仓库全名(owner/repo)'])]
    private string $fullName;

    #[ORM\Column(type: 'string', length: 255, options: ['comment' => '仓库名称'])]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, options: ['comment' => '仓库所有者'])]
    private string $owner;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => '仓库描述'])]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, options: ['comment' => '默认分支'])]
    private string $defaultBranch = 'master';

    #[ORM\Column(type: 'boolean', options: ['comment' => '是否私有'])]
    private bool $private = false;

    #[ORM\Column(type: 'boolean', options: ['comment' => '是否为Fork'])]
    private bool $fork = false;

    #[ORM\Column(type: 'string', length: 255, options: ['comment' => 'HTML URL'])]
    private string $htmlUrl;

    #[ORM\Column(type: 'string', length: 255, options: ['comment' => 'SSH URL'])]
    private string $sshUrl;

    #[ORM\Column(type: 'datetime_immutable', options: ['comment' => '最后推送时间'])]
    private \DateTimeImmutable $pushedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplication(): GiteeApplication
    {
        return $this->application;
    }

    public function setApplication(GiteeApplication $application): self
    {
        $this->application = $application;
        return $this;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function setOwner(string $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDefaultBranch(): string
    {
        return $this->defaultBranch;
    }

    public function setDefaultBranch(string $defaultBranch): self
    {
        $this->defaultBranch = $defaultBranch;
        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function setPrivate(bool $private): self
    {
        $this->private = $private;
        return $this;
    }

    public function isFork(): bool
    {
        return $this->fork;
    }

    public function setFork(bool $fork): self
    {
        $this->fork = $fork;
        return $this;
    }

    public function getHtmlUrl(): string
    {
        return $this->htmlUrl;
    }

    public function setHtmlUrl(string $htmlUrl): self
    {
        $this->htmlUrl = $htmlUrl;
        return $this;
    }

    public function getSshUrl(): string
    {
        return $this->sshUrl;
    }

    public function setSshUrl(string $sshUrl): self
    {
        $this->sshUrl = $sshUrl;
        return $this;
    }

    public function getPushedAt(): \DateTimeImmutable
    {
        return $this->pushedAt;
    }

    public function setPushedAt(\DateTimeImmutable $pushedAt): self
    {
        $this->pushedAt = $pushedAt;
        return $this;
    }}
