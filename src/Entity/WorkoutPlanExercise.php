<?php

namespace App\Entity;

use App\Enum\ExerciseTrackingMode;
use App\Enum\ProgressionType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class WorkoutPlanExercise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'exercises')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private WorkoutPlan $workoutPlan;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Exercise $exercise;

    #[ORM\Column]
    private int $position = 1;

    #[ORM\Column(enumType: ProgressionType::class)]
    private ProgressionType $progressionType = ProgressionType::Fixed;

    #[ORM\Column(nullable: true)]
    private ?int $plannedSets = null;

    #[ORM\Column(nullable: true)]
    private ?int $plannedRepMin = null;

    #[ORM\Column(nullable: true)]
    private ?int $plannedRepMax = null;

    #[ORM\Column(nullable: true)]
    private ?int $plannedDurationSeconds = null;

    #[ORM\Column(nullable: true)]
    private ?int $plannedRestSeconds = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

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

    public function getWorkoutPlan(): WorkoutPlan
    {
        return $this->workoutPlan;
    }

    public function setWorkoutPlan(WorkoutPlan $workoutPlan): self
    {
        $this->workoutPlan = $workoutPlan;
        $this->touch();

        return $this;
    }

    public function getExercise(): Exercise
    {
        return $this->exercise;
    }

    public function setExercise(Exercise $exercise): self
    {
        $this->exercise = $exercise;
        $this->touch();

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        if ($position < 1) {
            throw new \InvalidArgumentException('La posizione deve essere maggiore di zero.');
        }

        $this->position = $position;
        $this->touch();

        return $this;
    }

    public function getProgressionType(): ProgressionType
    {
        return $this->progressionType;
    }

    public function setProgressionType(ProgressionType $progressionType): self
    {
        $this->progressionType = $progressionType;
        $this->touch();

        return $this;
    }

    public function getPlannedSets(): ?int
    {
        return $this->plannedSets;
    }

    public function setPlannedSets(?int $plannedSets): self
    {
        $this->plannedSets = $plannedSets !== null && $plannedSets > 0 ? $plannedSets : null;
        $this->touch();

        return $this;
    }

    public function getPlannedRepMin(): ?int
    {
        return $this->plannedRepMin;
    }

    public function setPlannedRepMin(?int $plannedRepMin): self
    {
        $this->plannedRepMin = $plannedRepMin !== null && $plannedRepMin > 0 ? $plannedRepMin : null;
        $this->touch();

        return $this;
    }

    public function getPlannedRepMax(): ?int
    {
        return $this->plannedRepMax;
    }

    public function setPlannedRepMax(?int $plannedRepMax): self
    {
        $this->plannedRepMax = $plannedRepMax !== null && $plannedRepMax > 0 ? $plannedRepMax : null;
        $this->touch();

        return $this;
    }

    public function getPlannedDurationSeconds(): ?int
    {
        return $this->plannedDurationSeconds;
    }

    public function setPlannedDurationSeconds(?int $plannedDurationSeconds): self
    {
        $this->plannedDurationSeconds = $plannedDurationSeconds !== null && $plannedDurationSeconds > 0 ? $plannedDurationSeconds : null;
        $this->touch();

        return $this;
    }

    public function getPlannedRestSeconds(): ?int
    {
        return $this->plannedRestSeconds;
    }

    public function setPlannedRestSeconds(?int $plannedRestSeconds): self
    {
        $this->plannedRestSeconds = $plannedRestSeconds !== null && $plannedRestSeconds > 0 ? $plannedRestSeconds : null;
        $this->touch();

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes !== null ? trim($notes) : null;
        $this->touch();

        return $this;
    }

    public function getPrescriptionSummary(): string
    {
        if ($this->progressionType === ProgressionType::TreninoInvictus) {
            return 'Trenino Invictus: 4 serie automatiche in base allo step';
        }

        $trackingMode = $this->exercise->getTrackingMode();

        if ($trackingMode === ExerciseTrackingMode::Time || $trackingMode === ExerciseTrackingMode::IsometricTime) {
            return $this->formatSetsPrefix() . $this->formatSeconds($this->plannedDurationSeconds);
        }

        if ($trackingMode === ExerciseTrackingMode::CardioMachine || $trackingMode === ExerciseTrackingMode::TimeDistance) {
            return $this->plannedDurationSeconds !== null
                ? $this->formatSeconds($this->plannedDurationSeconds)
                : 'Durata libera';
        }

        if ($this->plannedRepMin !== null && $this->plannedRepMax !== null) {
            return sprintf('%s%d-%d reps', $this->formatSetsPrefix(), $this->plannedRepMin, $this->plannedRepMax);
        }

        if ($this->plannedRepMin !== null) {
            return sprintf('%s%d reps', $this->formatSetsPrefix(), $this->plannedRepMin);
        }

        return $this->plannedSets !== null ? sprintf('%d serie', $this->plannedSets) : 'Libero';
    }

    public function getRestSummary(): string
    {
        return $this->plannedRestSeconds !== null ? $this->formatSeconds($this->plannedRestSeconds) : 'Non impostato';
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function formatSetsPrefix(): string
    {
        return $this->plannedSets !== null ? sprintf('%dx', $this->plannedSets) : '';
    }

    private function formatSeconds(?int $seconds): string
    {
        if ($seconds === null) {
            return 'Tempo libero';
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes > 0 && $remainingSeconds > 0) {
            return sprintf('%d:%02d', $minutes, $remainingSeconds);
        }

        if ($minutes > 0) {
            return sprintf('%d min', $minutes);
        }

        return sprintf('%d sec', $remainingSeconds);
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
