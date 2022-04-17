<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\UserRoleRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: '`user_roles`')]
#[ORM\Entity(repositoryClass: UserRoleRepository::class)]
#[Gedmo\SoftDeleteable(fieldName:"deletedAt", timeAware:false)]
#[ApiResource(
    normalizationContext: ['groups' => ['UserRole:read']],
    denormalizationContext: ['groups' => ['UserRole:write']],
    collectionOperations: [
        'get' => [
            'security' => 'is_granted("ROLE_ADMIN")'
        ],
        'post' => [
            'security' => 'is_granted("ROLE_ADMIN")'
        ],
    ],
    itemOperations: [
        'get' => [
            'security' => 'is_granted("ROLE_ADMIN")'
        ],
        'put' => [
            'security' => 'is_granted("ROLE_ADMIN")'
        ],
        'patch' => [
            'security' => 'is_granted("ROLE_ADMIN")'
        ],
        'delete'
    ]
)]
#[UniqueEntity(
    fields: 'name',
    errorPath: 'name',
    message: 'This name is already use in other role.',
)]
#[UniqueEntity(
    fields: 'role',
    errorPath: 'role',
    message: 'This role is already use in other role.',
)]
class UserRole
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    #[ApiProperty(identifier: true)]
    #[Groups(['UserRole:read'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank(message: 'The name is required.')]
    #[Groups(['UserRole:read', 'UserRole:write'])]
    private $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank(message: 'The role is required.')]
    #[Groups(['UserRole:read', 'UserRole:write'])]
    private $role;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'userRoles')]
    private $users;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected $updatedAt;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addUserRole($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeUserRole($this);
        }

        return $this;
    }
    
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }
}
