<?php

namespace App\Entity;

use App\Enum\WorkoutGoal;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class WorkoutPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AppUser $appUser;

    #[ORM\Column(length: 160)]
    private string $name = '';

    #[ORM\Column(length: 180)]
    private string $slug = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(enumType: WorkoutGoal::class)]
    private WorkoutGoal $goal = WorkoutGoal::General;

    #[ORM\Column(nullable: true)]
    private ?int $suggestedDayOfWeek = null;

    #[ORM\Column]
    private bool $isActive = true;

    /** @var Collection<int, WorkoutPlanExercise> */
    #[ORM\OneToMany(mappedBy: 'workoutPlan', targetEntity: WorkoutPlanExercise::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $exercises;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->exercises = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppUser(): AppUser
    {
        return $this->appUser;
    }

    public function setAppUser(AppUser $appUser): self
    {
        $this->appUser = $appUser;
        $this->touch();

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description !== null ? trim($description) : null;
        $this->touch();

        return $this;
    }

    public function getGoal(): WorkoutGoal
    {
        return $this->goal;
    }

    public function setGoal(WorkoutGoal $goal): self
    {
        $this->goal = $goal;
        $this->touch();

        return $this;
    }

    public function getSuggestedDayOfWeek(): ?int
    {
        return $this->suggestedDayOfWeek;
    }

    public function setSuggestedDayOfWeek(?int $suggestedDayOfWeek): self
    {
        if ($suggestedDayOfWeek !== null && ($suggestedDayOfWeek < 1 || $suggestedDayOfWeek > 7)) {
            throw new \InvalidArgumentException('Il giorno consigliato deve essere tra 1 e 7.');
        }

        $this->suggestedDayOfWeek = $suggestedDayOfWeek;
        $this->touch();

        return $this;
    }

    public function getSuggestedDayLabel(): string
    {
        return match ($this->suggestedDayOfWeek) {
            1 => 'Lunedì',
            2 => 'Martedì',
            3 => 'Mercoledì',
            4 => 'Giovedì',
            5 => 'Venerdì',
            6 => 'Sabato',
            7 => 'Domenica',
            default => 'Non impostato',
        };
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        $this->touch();

        return $this;
    }

    /** @return Collection<int, WorkoutPlanExercise> */
    public function getExercises(): Collection
    {
        return $this->exercises;
    }

    public function addExercise(WorkoutPlanExercise $exercise): self
    {
        if (!$this->exercises->contains($exercise)) {
            $this->exercises->add($exercise);
            $exercise->setWorkoutPlan($this);
            $this->touch();
        }

        return $this;
    }

    public function removeExercise(WorkoutPlanExercise $exercise): self
    {
        if ($this->exercises->removeElement($exercise)) {
            $this->touch();
        }

        return $this;
    }

    public function getNextPosition(): int
    {
        $maxPosition = 0;
        foreach ($this->exercises as $exercise) {
            $maxPosition = max($maxPosition, $exercise->getPosition());
        }

        return $maxPosition + 1;
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
