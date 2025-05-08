<?php

namespace GiteeApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use GiteeApiBundle\Enum\GiteeScope;
use GiteeApiBundle\Repository\GiteeApplicationRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;

#[ORM\Entity(repositoryClass: GiteeApplicationRepository::class)]
#[ORM\Table(name: 'gitee_application', options: ['comment' => 'Gitee OAuth应用配置'])]
class GiteeApplication
{
    #[ListColumn(order: -1)]
    #[ExportColumn]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = 0;

    #[ORM\Column(type: 'string', length: 255, options: ['comment' => '应用名称'])]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, options: ['comment' => '客户端ID'])]
    private string $clientId;

    #[ORM\Column(type: 'string', length: 255, options: ['comment' => '客户端密钥'])]
    private string $clientSecret;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['comment' => '应用主页'])]
    private ?string $homepage = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => '应用描述'])]
    private ?string $description = null;

    /**
     * @var GiteeScope[]
     */
    #[ORM\Column(type: 'json', options: ['comment' => '授权作用域'])]
    private array $scopes;

    /**
     * @DateRangePickerField()
     */
    #[Filterable]
    #[IndexColumn]
    #[ListColumn(order: 98, sorter: true)]
    #[ExportColumn]
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    /**
     * @DateRangePickerField()
     */
    #[UpdateTimeColumn]
    #[ListColumn(order: 99, sorter: true)]
    #[Filterable]
    #[ExportColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeInterface $updateTime = null;

    public function __construct()
    {
        $this->scopes = GiteeScope::getDefaultScopes();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): self
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function setHomepage(?string $homepage): self
    {
        $this->homepage = $homepage;
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

    /**
     * @return GiteeScope[]
     */
    public function getScopes(): array
    {
        return array_map(
            fn(string $scope) => GiteeScope::from($scope),
            $this->scopes
        );
    }

    /**
     * @param GiteeScope[] $scopes
     */
    public function setScopes(array $scopes): self
    {
        $this->scopes = array_map(
            fn(GiteeScope $scope) => $scope->value,
            $scopes
        );
        return $this;
    }

    public function getScopesAsString(): string
    {
        return GiteeScope::toString($this->getScopes());
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
