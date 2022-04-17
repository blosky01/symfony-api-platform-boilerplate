<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\GroupFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use App\Filter\FullTextSearchFilter;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`users`')]
#[ApiResource(
    normalizationContext: [
        'groups' => ['User:read'],
    ],
    denormalizationContext: [
        'groups' => ['User:write']
    ],
    collectionOperations: [
        'get' => [
            'security' => 'is_granted("ROLE_ADMIN")',
        ],
        'post' => [
            'openapi_context' => [
                'tags' => ['Auth']
            ],
            'path' => '/auth/register'
        ]
    ],
    itemOperations: [
        'get' => [
            'security' => 'is_granted("ROLE_ADMIN") or object === user'
        ],
        // 'put' => [
        //     'security' => 'is_granted("ROLE_ADMIN") or object === user'
        // ],
        'patch' => [
            'security' => 'is_granted("ROLE_ADMIN") or object === user'
        ]
    ]
)]
#[Gedmo\SoftDeleteable(fieldName:"deletedAt", timeAware:false)]
#[UniqueEntity(
    fields: 'email',
    errorPath: 'email',
    message: 'This email is already use in other user.',
)]
#[UniqueEntity(
    fields: 'username',
    errorPath: 'username',
    message: 'This username is already use in other user.',
)]
#[ApiFilter(SearchFilter::class, properties: [
    'id' => 'exact',
    'email' => 'ipartial',
    'username' => 'ipartial'
])]
#[ApiFilter(FullTextSearchFilter::class, properties: [
    'id' => 'exact',
    'email' => 'ipartial',
    'username' => 'ipartial'
])]
#[ApiFilter(OrderFilter::class, properties: ['id', 'createdAt'])]
#[ApiFilter(DateFilter::class, properties: ['createdAt'])]
#[ApiFilter(GroupFilter::class, arguments: ['parameterName' => 'groups', 'overrideDefaultGroups' => true])]
#[ApiFilter(PropertyFilter::class, arguments: [
    'parameterName' => 'properties',
    'overrideDefaultProperties' => true
])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    #[ApiProperty(identifier: true)]
    #[Groups(['User:read'])]
    private $id;

    #[Assert\Email(message: 'The email {{ value }} is not a valid email.')]
    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['User:read', 'User:write'])]
    private $email;

    #[ORM\Column(type: 'boolean', options: ["default" => false])]
    private $emailVerify = false;

    #[ORM\Column(type: 'string', length: 80, unique: true)]
    #[Assert\NotBlank(message: 'The username is required.')]
    #[Assert\Length(min: 4, minMessage: 'The username must have a minimum of 4 characters.')]
    #[Groups(['User:read', 'User:write'])]
    private $username;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'The firstName is required.')]
    #[Assert\Length(min: 1, minMessage: 'The firstName must have a minimum of 1 characters.')]
    #[Groups(['User:read', 'User:write'])]
    private $firstName;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'The lastName is required.')]
    #[Assert\Length(min: 4, minMessage: 'The lastName must have a minimum of 4 characters.')]
    #[Groups(['User:read', 'User:write'])]
    private $lastName;

    #[ORM\OneToOne(mappedBy: 'owner', targetEntity: UserImage::class, cascade: ['persist', 'remove'])]
    #[ApiProperty(iri: 'http://schema.org/image')]
    #[Groups(['User:read', 'User:write'])]
    private $userImage;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'The password is required.')]
    #[Assert\Length(min: 8, minMessage: 'The password must have a minimum of 8 characters.')]
    #[Assert\Regex(pattern: '/^.*(?=.*?[0-9]).*$/', message: 'The password must have at least one number.')]
    #[Assert\Regex(pattern: '/^.*(?=.*?[a-zA-Z]).*$/', message: 'The password must have at least one letter.')]
    #[Groups(['User:write'])]
    private $password;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $googleId;

    #[ORM\Column(type: 'text', nullable: true)]
    private $avatar;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $hostedDomain;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'es'])]
    private $locale = 'es';

    #[ORM\ManyToMany(targetEntity: UserRole::class, inversedBy: 'users')]
    #[Groups(['User:write'])]
    private $userRoles;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected $updatedAt;

    public function __construct()
    {
        $this->userRoles = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }
    
    public function getEmailVerify(): ?bool
    {
        return $this->emailVerify;
    }

    public function setEmailVerify(bool $emailVerify): self
    {
        $this->emailVerify = $emailVerify;

        return $this;
    }
    
    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }
    
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    #[Groups(['User:read'])]
    public function getRoles(): Array
    {
        $roles = array_map(fn(UserRole $role) => $role->getRole(), $this->userRoles->toArray());
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }

    public function addUserRole(UserRole $userRole): self
    {
        if (!$this->userRoles->contains($userRole)) {
            $this->userRoles[] = $userRole;
        }

        return $this;
    }

    public function removeUserRole(UserRole $userRole): self
    {
        $this->userRoles->removeElement($userRole);

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getUserImage(): ?UserImage
    {
        return $this->userImage;
    }

    public function setUserImage(?UserImage $userImage): self
    {
        // unset the owning side of the relation if necessary
        if ($userImage === null && $this->userImage !== null) {
            $this->userImage->setOwner(null);
        }

        // set the owning side of the relation if necessary
        if ($userImage !== null && $userImage->getOwner() !== $this) {
            $userImage->setOwner($this);
        }

        $this->userImage = $userImage;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getHostedDomain(): ?string
    {
        return $this->hostedDomain;
    }

    public function setHostedDomain(?string $hostedDomain): self
    {
        $this->hostedDomain = $hostedDomain;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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
