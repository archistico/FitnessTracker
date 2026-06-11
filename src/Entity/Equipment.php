<?php

namespace App\Entity;

use App\Enum\EquipmentType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Equipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name = '';

    #[ORM\Column(length: 140, unique: true)]
    private string $slug = '';

    #[ORM\Column(enumType: EquipmentType::class)]
    private EquipmentType $type = EquipmentType::Accessory;

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $usageInstructions = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column]
    private bool $isMachine = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
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
        $this->name = trim($name);
        $this->touch();

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = trim($slug);
        $this->touch();

        return $this;
    }

    public function getType(): EquipmentType
    {
        return $this->type;
    }

    public function setType(EquipmentType $type): self
    {
        $this->type = $type;
        $this->touch();

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = trim($description);
        $this->touch();

        return $this;
    }

    public function getUsageInstructions(): ?string
    {
        return $this->usageInstructions;
    }

    public function setUsageInstructions(?string $usageInstructions): self
    {
        $this->usageInstructions = $usageInstructions !== null ? trim($usageInstructions) : null;
        $this->touch();

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath !== null ? trim($imagePath) : null;
        $this->touch();

        return $this;
    }

    public function isMachine(): bool
    {
        return $this->isMachine;
    }

    public function setIsMachine(bool $isMachine): self
    {
        $this->isMachine = $isMachine;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
