<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[Vich\Uploadable]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            inputFormats: ['multipart' => ['multipart/form-data']],
            deserialize: false
        ),
        new Put(
            inputFormats: ['multipart' => ['multipart/form-data']],
            deserialize: false
        ),
        new Delete()
    ],
    normalizationContext: ['groups' => ['product:read']],
    denormalizationContext: ['groups' => ['product:write']]
)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'product:write'])]
    #[Assert\NotBlank(message: 'Le nom du produit est obligatoire')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['product:read', 'product:write'])]
    #[Assert\NotBlank(message: 'Le prix est obligatoire')]
    #[Assert\Positive(message: 'Le prix doit être positif')]
    #[Assert\Range(min: 0, max: 999999.99, notInRangeMessage: 'Le prix doit être entre {{ min }} et {{ max }}')]
    private ?float $price = null;

    #[ORM\Column]
    #[Groups(['product:read', 'product:write'])]
    #[Assert\NotBlank(message: 'La quantité en stock est obligatoire')]
    #[Assert\PositiveOrZero(message: 'La quantité en stock doit être positive ou nulle')]
    #[Assert\Range(min: 0, max: 999999, notInRangeMessage: 'La quantité doit être entre {{ min }} et {{ max }}')]
    private ?int $stockQuantity = null;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'imageName')]
    #[Groups(['product:write'])]
    #[Assert\Image(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Veuillez uploader une image valide (JPEG, PNG ou WebP)',
        maxSizeMessage: 'L\'image ne peut pas dépasser {{ limit }}'
    )]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $imageName = null;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['product:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStockQuantity(): ?int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): static
    {
        $this->stockQuantity = $stockQuantity;

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    /**
     * Génère l'URL de l'image
     */
    #[Groups(['product:read'])]
    public function getImageUrl(): ?string
    {
        if (!$this->imageName) {
            return null;
        }

        return '/uploads/products/' . $this->imageName;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
