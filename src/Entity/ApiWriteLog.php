<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\ApiWriteLogRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: ApiWriteLogRepository::class)]
#[ApiResource(
    itemOperations: [
        'get' => [
            'security' => 'is_granted("ROLE_ADMIN")',
            'openapi_context' => [
                'tags' => ['Log']
            ],
        ]
    ],
    collectionOperations: [
        'get' => [
            'security' => 'is_granted("ROLE_ADMIN")',
            'openapi_context' => [
                'tags' => ['Log']
            ],
        ]
    ]
)]
class ApiWriteLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'json')]
    private array $context = [];

    #[ORM\Column(type: 'smallint')]
    private int $level;

    #[ORM\Column(type: 'string', length: 50)]
    private string $levelName;

    #[ORM\Column(type: 'json')]
    private array $extra = [];

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $recordBy;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getLevelName(): ?string
    {
        return $this->levelName;
    }

    public function setLevelName(string $levelName): self
    {
        $this->levelName = $levelName;

        return $this;
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }

    public function setExtra(array $extra): self
    {
        $this->extra = $extra;

        return $this;
    }

    public function getRecordBy(): ?string
    {
        return $this->recordBy;
    }

    public function setRecordBy(?string $recordBy): self
    {
        $this->recordBy = $recordBy;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}
