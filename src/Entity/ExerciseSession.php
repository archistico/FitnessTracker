<?php

namespace App\Entity;

use App\Enum\ExerciseSessionStatus;
use App\Enum\ProgressionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ExerciseSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'exerciseSessions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private WorkoutSession $workoutSession;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private Exercise $exercise;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?WorkoutPlanExercise $workoutPlanExercise = null;

    #[ORM\Column]
    private int $position = 1;

    #[ORM\Column(enumType: ProgressionType::class)]
    private ProgressionType $progressionType = ProgressionType::Fixed;

    #[ORM\Column(nullable: true)]
    private ?int $progressionStepNumber = null;

    #[ORM\Column(enumType: ExerciseSessionStatus::class)]
    private ExerciseSessionStatus $status = ExerciseSessionStatus::Planned;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $skipReason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    /** @var Collection<int, SetLog> */
    #[ORM\OneToMany(mappedBy: 'exerciseSession', targetEntity: SetLog::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['setNumber' => 'ASC'])]
    private Collection $setLogs;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->setLogs = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorkoutSession(): WorkoutSession
    {
        return $this->workoutSession;
    }

    public function setWorkoutSession(WorkoutSession $workoutSession): self
    {
        $this->workoutSession = $workoutSession;
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

    public function getWorkoutPlanExercise(): ?WorkoutPlanExercise
    {
        return $this->workoutPlanExercise;
    }

    public function setWorkoutPlanExercise(?WorkoutPlanExercise $workoutPlanExercise): self
    {
        $this->workoutPlanExercise = $workoutPlanExercise;
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

    public function getProgressionStepNumber(): ?int
    {
        return $this->progressionStepNumber;
    }

    public function setProgressionStepNumber(?int $progressionStepNumber): self
    {
        $this->progressionStepNumber = $progressionStepNumber !== null && $progressionStepNumber > 0 ? $progressionStepNumber : null;
        $this->touch();

        return $this;
    }

    public function getStatus(): ExerciseSessionStatus
    {
        return $this->status;
    }

    public function setStatus(ExerciseSessionStatus $status): self
    {
        $this->status = $status;
        $this->touch();

        return $this;
    }

    public function getSkipReason(): ?string
    {
        return $this->skipReason;
    }

    public function setSkipReason(?string $skipReason): self
    {
        $this->skipReason = $skipReason !== null ? trim($skipReason) : null;
        if ($this->skipReason === '') {
            $this->skipReason = null;
        }
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

    /** @return Collection<int, SetLog> */
    public function getSetLogs(): Collection
    {
        return $this->setLogs;
    }

    public function addSetLog(SetLog $setLog): self
    {
        if (!$this->setLogs->contains($setLog)) {
            $this->setLogs->add($setLog);
            $setLog->setExerciseSession($this);
            $this->touch();
        }

        return $this;
    }

    public function markSkipped(?string $reason = null): self
    {
        $this->status = ExerciseSessionStatus::Skipped;
        $this->setSkipReason($reason);

        foreach ($this->setLogs as $setLog) {
            $setLog->setSkipped(true)->setSkipReason($reason);
        }

        $this->touch();

        return $this;
    }

    public function refreshStatusFromSetData(): self
    {
        $planned = $this->setLogs->count();
        $completed = 0;
        $skipped = 0;

        foreach ($this->setLogs as $setLog) {
            if ($setLog->isSkipped()) {
                ++$skipped;
                continue;
            }

            if ($setLog->hasActualData()) {
                ++$completed;
            }
        }

        $closed = $completed + $skipped;

        $this->status = match (true) {
            $planned === 0 => ExerciseSessionStatus::Skipped,
            $closed === 0 => ExerciseSessionStatus::Skipped,
            $skipped >= $planned => ExerciseSessionStatus::Skipped,
            $closed >= $planned => $skipped > 0 ? ExerciseSessionStatus::Partial : ExerciseSessionStatus::Completed,
            default => ExerciseSessionStatus::Partial,
        };
        $this->touch();

        return $this;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
