<?php

declare(strict_types=1);

namespace GiteeApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use GiteeApiBundle\Repository\GiteeAccessTokenRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: GiteeAccessTokenRepository::class)]
#[ORM\Table(name: 'gitee_access_token', options: ['comment' => 'Gitee OAuth访问令牌'])]
class GiteeAccessToken implements \Stringable
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

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'Access Token'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $accessToken;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'Refresh Token'])]
    #[Assert\Length(max: 255)]
    private ?string $refreshToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '过期时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $expireTime = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'Gitee用户名'])]
    #[Assert\Length(max: 255)]
    private ?string $giteeUsername = null;

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

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getExpireTime(): ?\DateTimeImmutable
    {
        return $this->expireTime;
    }

    public function setExpireTime(?\DateTimeImmutable $expireTime): void
    {
        $this->expireTime = $expireTime;
    }

    /**
     * @deprecated Use getExpireTime() instead
     */
    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expireTime;
    }

    /**
     * @deprecated Use setExpireTime() instead
     */
    public function setExpiresAt(?\DateTimeImmutable $expiresAt): void
    {
        $this->setExpireTime($expiresAt);
    }

    public function getGiteeUsername(): ?string
    {
        return $this->giteeUsername;
    }

    public function setGiteeUsername(?string $giteeUsername): void
    {
        $this->giteeUsername = $giteeUsername;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->getUserId(), $this->getGiteeUsername() ?? 'N/A');
    }
}
