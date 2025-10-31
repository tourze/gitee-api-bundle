<?php

declare(strict_types=1);

namespace GiteeApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use GiteeApiBundle\Repository\GiteeRepositoryRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: GiteeRepositoryRepository::class)]
#[ORM\Table(name: 'gitee_repository', options: ['comment' => 'Gitee仓库信息'])]
#[ORM\UniqueConstraint(columns: ['user_id', 'application_id', 'full_name'])]
class GiteeRepository implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GiteeApplication::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GiteeApplication $application;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '用户ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $userId;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '仓库全名(owner/repo)'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $fullName;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '仓库名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '仓库所有者'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $owner;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '仓库描述'])]
    #[Assert\Length(max: 1000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '默认分支'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $defaultBranch = 'master';

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否私有'])]
    #[Assert\Type(type: 'bool')]
    private bool $private = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否为Fork'])]
    #[Assert\Type(type: 'bool')]
    private bool $fork = false;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'HTML URL'])]
    #[Assert\NotBlank]
    #[Assert\Url]
    #[Assert\Length(max: 255)]
    private string $htmlUrl;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'SSH URL'])]
    #[Assert\NotBlank]
    #[Assert\Url]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(pattern: '/^(ssh:\/\/)?git@[\w\.-]+:[\w\/-]+\.git$/', message: 'Invalid SSH URL format')]
    private string $sshUrl;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '最后推送时间'])]
    #[Assert\NotNull]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private \DateTimeImmutable $pushTime;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplication(): GiteeApplication
    {
        return $this->application;
    }

    public function setApplication(GiteeApplication $application): void
    {
        $this->application = $application;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function setOwner(string $owner): void
    {
        $this->owner = $owner;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDefaultBranch(): string
    {
        return $this->defaultBranch;
    }

    public function setDefaultBranch(string $defaultBranch): void
    {
        $this->defaultBranch = $defaultBranch;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function setPrivate(bool $private): void
    {
        $this->private = $private;
    }

    public function isFork(): bool
    {
        return $this->fork;
    }

    public function setFork(bool $fork): void
    {
        $this->fork = $fork;
    }

    public function getHtmlUrl(): string
    {
        return $this->htmlUrl;
    }

    public function setHtmlUrl(string $htmlUrl): void
    {
        $this->htmlUrl = $htmlUrl;
    }

    public function getSshUrl(): string
    {
        return $this->sshUrl;
    }

    public function setSshUrl(string $sshUrl): void
    {
        $this->sshUrl = $sshUrl;
    }

    public function getPushTime(): \DateTimeImmutable
    {
        return $this->pushTime;
    }

    public function setPushTime(\DateTimeImmutable $pushTime): void
    {
        $this->pushTime = $pushTime;
    }

    /**
     * @deprecated Use getPushTime() instead
     */
    public function getPushedAt(): \DateTimeImmutable
    {
        return $this->pushTime;
    }

    /**
     * @deprecated Use setPushTime() instead
     */
    public function setPushedAt(\DateTimeImmutable $pushedAt): void
    {
        $this->setPushTime($pushedAt);
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}
