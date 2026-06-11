<?php

namespace App\Entity;

use App\Enum\WorkoutSessionStatus;
use App\Enum\WorkoutSessionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class WorkoutSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AppUser $appUser;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?WorkoutPlan $workoutPlan = null;

    #[ORM\Column(enumType: WorkoutSessionType::class)]
    private WorkoutSessionType $sessionType = WorkoutSessionType::Training;

    #[ORM\Column(enumType: WorkoutSessionStatus::class)]
    private WorkoutSessionStatus $status = WorkoutSessionStatus::InProgress;

    #[ORM\Column]
    private \DateTimeImmutable $sessionDate;

    #[ORM\Column]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    /** @var Collection<int, ExerciseSession> */
    #[ORM\OneToMany(mappedBy: 'workoutSession', targetEntity: ExerciseSession::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $exerciseSessions;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->exerciseSessions = new ArrayCollection();
        $this->sessionDate = new \DateTimeImmutable('today');
        $this->startedAt = new \DateTimeImmutable();
        $this->createdAt = $this->startedAt;
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

    public function getWorkoutPlan(): ?WorkoutPlan
    {
        return $this->workoutPlan;
    }

    public function setWorkoutPlan(?WorkoutPlan $workoutPlan): self
    {
        $this->workoutPlan = $workoutPlan;
        $this->touch();

        return $this;
    }

    public function getSessionType(): WorkoutSessionType
    {
        return $this->sessionType;
    }

    public function setSessionType(WorkoutSessionType $sessionType): self
    {
        $this->sessionType = $sessionType;
        $this->touch();

        return $this;
    }

    public function getStatus(): WorkoutSessionStatus
    {
        return $this->status;
    }

    public function setStatus(WorkoutSessionStatus $status): self
    {
        $this->status = $status;
        $this->touch();

        return $this;
    }

    public function getSessionDate(): \DateTimeImmutable
    {
        return $this->sessionDate;
    }

    public function setSessionDate(\DateTimeImmutable $sessionDate): self
    {
        $this->sessionDate = $sessionDate;
        $this->touch();

        return $this;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;
        $this->touch();

        return $this;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeImmutable $endedAt): self
    {
        $this->endedAt = $endedAt;
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

    /** @return Collection<int, ExerciseSession> */
    public function getExerciseSessions(): Collection
    {
        return $this->exerciseSessions;
    }

    public function addExerciseSession(ExerciseSession $exerciseSession): self
    {
        if (!$this->exerciseSessions->contains($exerciseSession)) {
            $this->exerciseSessions->add($exerciseSession);
            $exerciseSession->setWorkoutSession($this);
            $this->touch();
        }

        return $this;
    }

    public function removeExerciseSession(ExerciseSession $exerciseSession): self
    {
        if ($this->exerciseSessions->removeElement($exerciseSession)) {
            $this->touch();
        }

        return $this;
    }

    public function getCompletedSetCount(): int
    {
        $count = 0;
        foreach ($this->exerciseSessions as $exerciseSession) {
            foreach ($exerciseSession->getSetLogs() as $setLog) {
                if (!$setLog->isSkipped() && $setLog->hasActualData()) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    public function getSkippedSetCount(): int
    {
        $count = 0;
        foreach ($this->exerciseSessions as $exerciseSession) {
            foreach ($exerciseSession->getSetLogs() as $setLog) {
                if ($setLog->isSkipped()) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    public function getPlannedSetCount(): int
    {
        $count = 0;
        foreach ($this->exerciseSessions as $exerciseSession) {
            $count += $exerciseSession->getSetLogs()->count();
        }

        return $count;
    }

    public function closeFromSetData(): self
    {
        $planned = $this->getPlannedSetCount();
        $closed = $this->getCompletedSetCount() + $this->getSkippedSetCount();

        $this->status = $planned > 0 && $closed >= $planned
            ? ($this->getSkippedSetCount() > 0 ? WorkoutSessionStatus::Partial : WorkoutSessionStatus::Completed)
            : WorkoutSessionStatus::Partial;
        $this->endedAt = new \DateTimeImmutable();
        $this->touch();

        foreach ($this->exerciseSessions as $exerciseSession) {
            $exerciseSession->refreshStatusFromSetData();
        }

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
