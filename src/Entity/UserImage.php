<?php

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