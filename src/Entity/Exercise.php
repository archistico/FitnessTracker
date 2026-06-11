<?php

namespace App\Entity;

use App\Enum\ExerciseTrackingMode;
use App\Enum\ExerciseType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Exercise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    private string $name = '';

    #[ORM\Column(length: 180, unique: true)]
    private string $slug = '';

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $executionInstructions = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $primaryMuscles = [];

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $secondaryMuscles = [];

    #[ORM\Column(enumType: ExerciseTrackingMode::class)]
    private ExerciseTrackingMode $trackingMode = ExerciseTrackingMode::WeightReps;

    #[ORM\Column(enumType: ExerciseType::class)]
    private ExerciseType $exerciseType = ExerciseType::Strength;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Equipment $defaultEquipment = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $secondaryEquipmentNotes = null;

    #[ORM\Column]
    private float $defaultIncrementKg = 2.5;

    #[ORM\Column]
    private bool $isFundamental = false;

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

    public function getExecutionInstructions(): ?string
    {
        return $this->executionInstructions;
    }

    public function setExecutionInstructions(?string $executionInstructions): self
    {
        $this->executionInstructions = $executionInstructions !== null ? trim($executionInstructions) : null;
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

    /** @return list<string> */
    public function getPrimaryMuscles(): array
    {
        return $this->primaryMuscles;
    }

    /** @param list<string> $primaryMuscles */
    public function setPrimaryMuscles(array $primaryMuscles): self
    {
        $this->primaryMuscles = array_values($primaryMuscles);
        $this->touch();

        return $this;
    }

    /** @return list<string> */
    public function getSecondaryMuscles(): array
    {
        return $this->secondaryMuscles;
    }

    /** @param list<string> $secondaryMuscles */
    public function setSecondaryMuscles(array $secondaryMuscles): self
    {
        $this->secondaryMuscles = array_values($secondaryMuscles);
        $this->touch();

        return $this;
    }

    public function getTrackingMode(): ExerciseTrackingMode
    {
        return $this->trackingMode;
    }

    public function setTrackingMode(ExerciseTrackingMode $trackingMode): self
    {
        $this->trackingMode = $trackingMode;
        $this->touch();

        return $this;
    }

    public function getExerciseType(): ExerciseType
    {
        return $this->exerciseType;
    }

    public function setExerciseType(ExerciseType $exerciseType): self
    {
        $this->exerciseType = $exerciseType;
        $this->touch();

        return $this;
    }

    public function getDefaultEquipment(): ?Equipment
    {
        return $this->defaultEquipment;
    }

    public function setDefaultEquipment(?Equipment $defaultEquipment): self
    {
        $this->defaultEquipment = $defaultEquipment;
        $this->touch();

        return $this;
    }

    public function getSecondaryEquipmentNotes(): ?string
    {
        return $this->secondaryEquipmentNotes;
    }

    public function setSecondaryEquipmentNotes(?string $secondaryEquipmentNotes): self
    {
        $this->secondaryEquipmentNotes = $secondaryEquipmentNotes !== null ? trim($secondaryEquipmentNotes) : null;
        $this->touch();

        return $this;
    }

    public function getDefaultIncrementKg(): float
    {
        return $this->defaultIncrementKg;
    }

    public function setDefaultIncrementKg(float $defaultIncrementKg): self
    {
        $this->defaultIncrementKg = $defaultIncrementKg;
        $this->touch();

        return $this;
    }

    public function isFundamental(): bool
    {
        return $this->isFundamental;
    }

    public function setIsFundamental(bool $isFundamental): self
    {
        $this->isFundamental = $isFundamental;
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
