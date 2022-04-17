# Create Entities

## User Entity

Los permisos en Symfony siempre están vinculados a un objeto de usuario. Si necesita proteger (partes de) su aplicación, debe crear una clase de usuario. Esta es una clase que implementa [UserInterface](https://github.com/symfony/symfony/blob/6.0/src/Symfony/Component/Security/Core/User/UserInterface.php). Esta suele ser una entidad de Doctrine, pero también puede usar una clase de usuario de seguridad dedicada.

La forma más fácil de generar una clase de usuario es usando el comando `make:user` del MakerBundle:

```console
    $ php bin/console make:user

    The name of the security user class (e.g. User) [User]:
    > User

    Do you want to store user data in the database (via Doctrine)? (yes/no) [yes]:
    > yes

    Enter a property name that will be the unique "display" name for the user (e.g. email, username, uuid) [email]:
    > email

    Will this app need to hash/check user passwords? Choose No if passwords are not needed or will be checked/hashed by some other system (e.g. a single sign-on server).

    Does this app need to hash/check user passwords? (yes/no) [yes]:
    > yes

    created: src/Entity/User.php
    created: src/Repository/UserRepository.php
    updated: src/Entity/User.php
    updated: config/packages/security.yaml
```

> Este ejemplo no es realmente la entidad devuelta por `make:user`, hay campos extras

```php
<?php
// src/Entity/User.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
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

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['User:read', 'User:write'])]
    private $email;

    #[ORM\Column(type: 'boolean', options: ["default" => false])]
    private $emailVerify = false;

    #[ORM\Column(type: 'string', length: 80, unique: true)]
    #[Groups(['User:read', 'User:write'])]
    private $username;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['User:read', 'User:write'])]
    private $firstName;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['User:read', 'User:write'])]
    private $lastName;

    #[ORM\OneToOne(mappedBy: 'owner', targetEntity: UserImage::class, cascade: ['persist', 'remove'])]
    #[ApiProperty(iri: 'http://schema.org/image')]
    #[Groups(['User:read', 'User:write'])]
    private $userImage;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['User:write'])]
    private $password;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'es'])]
    private $locale;

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
```

## User Roles

```php
<?php
// src/Entity/UserRoles.php

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
use Symfony\Component\Serializer\Annotation\Groups;

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
    #[Groups(['UserRole:read', 'UserRole:write'])]
    private $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
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
```

## User Image

Esta entidad tiene más complejidad, ya que tendremos que manejar la subida de ficheros.

### Paso 1: Descargar el Bundle

Haremos el manejo de la carga de archivos en la API, con la ayuda de [VichUploaderBundle](https://github.com/dustin10/VichUploaderBundle):

```console
composer require vich/uploader-bundle
```

### Paso 2: Configurar el Bundle

Esto creará un nuevo archivo de configuración (`config/packages/vich_uploader.yaml`) que deberá cambiar ligeramente para que se vea así:

```yaml
vich_uploader:
    db_driver: orm

    mappings:
        users:
           uri_prefix: /images/users
           upload_destination: '%kernel.project_dir%/var/images/users'
```

### Paso 3: Crear entidad UserImage

```php
<?php
// src/Entity/UserImage.php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\Media\CreateUserImageController;
use App\Controller\Media\GetUserImageController;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @Vich\Uploadable
 */
#[ORM\Entity]
#[ApiResource(
    iri: 'http://schema.org/MediaObject',
    normalizationContext: ['groups' => ['UserImage:read']],
    itemOperations: [
        'get' => [
            'controller' => GetUserImageController::class,
            'security' => 'is_granted("ROLE_ADMIN") or object.getOwner() === user'
        ],
        'delete' => [
            'security' => 'is_granted("ROLE_ADMIN") or object.getOwner() === user'
        ],
    ],
    collectionOperations: [
        'get' => [
            'security' => 'is_granted("ROLE_ADMIN") or object.getOwner() === user'
        ],
        'post' => [
            'security' => 'is_granted("ROLE_USER")',
            'controller' => CreateUserImageController::class,
            'deserialize' => false,
            'validation_groups' => ['Default', 'UserImage:write'],
            'openapi_context' => [
                'requestBody' => [
                    'content' => [
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]
)]
class UserImage
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    #[ApiProperty(identifier: true)]
    private $id;

    #[ApiProperty(iri: 'http://schema.org/contentUrl')]
    #[Groups(['UserImage:read'])]
    public ?string $contentUrl = null;

    /**
     * @Vich\UploadableField(mapping="users", fileNameProperty="filePath")
     */
    #[Assert\NotNull(groups: ['UserImage:write'])]
    public ?File $file = null;

    #[ORM\Column(nullable: true)]
    public ?string $filePath = null;

    #[ORM\OneToOne(inversedBy: 'userImage', targetEntity: User::class)]
    #[Groups(['UserImage:read'])]
    private ?User $owner;

    public function getId(): ?string
    {
        return $this->id;
    }
    
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
```

### Paso 4: Creamos un custom controller para método POST

```php
<?php
#src/Controller/Media/CreateUserImageController.php

namespace App\Controller\Media;

use App\Entity\UserImage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class CreateUserImageController extends AbstractController
{
    public function __invoke(Request $request): UserImage
    {
        $uploadedFile = $request->files->get('file');
        
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }

        $userImage = new UserImage();
        $userImage->file = $uploadedFile;

        return $userImage;
    }
}
```

### Paso 5: Creamos un normalizador

Creamos un normalizador para establecer la propiedad `contentUrl`:

```php
<?php
#src/Serializer/UserImageNormalizer.php

namespace App\Serializer;

use App\Entity\UserImage;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Vich\UploaderBundle\Storage\StorageInterface;

final class UserImageNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'USER_IMAGE_NORMALIZER_ALREADY_CALLED';

    public function __construct(private StorageInterface $storage)
    {
    }

    public function normalize($object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::ALREADY_CALLED] = true;

        $object->contentUrl = $this->storage->resolveUri($object, 'file');

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof UserImage;
    }
}
```

### Paso 6: Creamos un custom controller para método GET

```php
<?php
#src/Controller/Media/GetUserImageController.php

namespace App\Controller\Media;

use App\Entity\UserImage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class GetUserImageController extends AbstractController
{
    public function __invoke(UserImage $data): BinaryFileResponse
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/var/images/users/' . $data->filePath;

        return new BinaryFileResponse($filePath);
    }
}
```

« [Complete Reference](../Readme.md) • [Hash Password](HashPassword.md) »