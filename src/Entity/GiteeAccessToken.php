<?php

namespace GiteeApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use GiteeApiBundle\Repository\GiteeAccessTokenRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;

#[ORM\Entity(repositoryClass: GiteeAccessTokenRepository::class)]
#[ORM\Table(name: 'gitee_access_token', options: ['comment' => 'Gitee OAuth访问令牌'])]
class GiteeAccessToken
{
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

    #[ORM\Column(type: 'string', length: 255, options: ['comment' => 'Access Token'])]
    private string $accessToken;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['comment' => 'Refresh Token'])]
    private ?string $refreshToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, options: ['comment' => '过期时间'])]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['comment' => 'Gitee用户名'])]
    private ?string $giteeUsername = null;

    #[Filterable]
    #[IndexColumn]
    #[ListColumn(order: 98, sorter: true)]
    #[ExportColumn]
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ListColumn(order: 99, sorter: true)]
    #[Filterable]
    #[ExportColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeInterface $updateTime = null;

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

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getGiteeUsername(): ?string
    {
        return $this->giteeUsername;
    }

    public function setGiteeUsername(?string $giteeUsername): self
    {
        $this->giteeUsername = $giteeUsername;
        return $this;
    }

    public function setCreateTime(?\DateTimeInterface $createdAt): void
    {
        $this->createTime = $createdAt;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }
}
