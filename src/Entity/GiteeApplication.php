<?php

declare(strict_types=1);

namespace GiteeApiBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use GiteeApiBundle\Enum\GiteeScope;
use GiteeApiBundle\Repository\GiteeApplicationRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity(repositoryClass: GiteeApplicationRepository::class)]
#[ORM\Table(name: 'gitee_application', options: ['comment' => 'Gitee OAuth应用配置'])]
class GiteeApplication implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '应用名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '客户端ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $clientId;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '客户端密钥'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $clientSecret;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '应用主页'])]
    #[Assert\Url]
    #[Assert\Length(max: 255)]
    private ?string $homepage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '应用描述'])]
    #[Assert\Length(max: 1000)]
    private ?string $description = null;

    /**
     * @var string[]
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '授权作用域'])]
    #[Assert\NotBlank]
    #[Assert\Type(type: 'array')]
    private array $scopes;

    public function __construct()
    {
        $this->setScopes(GiteeScope::getDefaultScopes());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    public function getHomepage(): ?string
    {
        return $this->homepage;
    }

    public function setHomepage(?string $homepage): void
    {
        $this->homepage = $homepage;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return GiteeScope[]
     */
    public function getScopes(): array
    {
        return array_map(
            fn (string $scope) => GiteeScope::from($scope),
            $this->scopes
        );
    }

    /**
     * @param GiteeScope[] $scopes
     */
    public function setScopes(array $scopes): void
    {
        $this->scopes = array_map(
            fn (GiteeScope $scope) => $scope->value,
            $scopes
        );
    }

    public function getScopesAsString(): string
    {
        return GiteeScope::toString($this->getScopes());
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
