<?php

namespace App\Entity;

use App\Enum\PerceivedEffort;
use App\Enum\PerceivedLoad;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class SetLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'setLogs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ExerciseSession $exerciseSession;

    #[ORM\Column]
    private int $setNumber = 1;

    #[ORM\Column(nullable: true)]
    private ?int $targetRepMin = null;

    #[ORM\Column(nullable: true)]
    private ?int $targetRepMax = null;

    #[ORM\Column(nullable: true)]
    private ?float $targetWeightKg = null;

    #[ORM\Column(nullable: true)]
    private ?int $targetDurationSeconds = null;

    #[ORM\Column(nullable: true)]
    private ?int $restSecondsPlanned = null;

    #[ORM\Column(nullable: true)]
    private ?float $actualWeightKg = null;

    #[ORM\Column(nullable: true)]
    private ?int $actualReps = null;

    #[ORM\Column(nullable: true)]
    private ?int $actualDurationSeconds = null;

    #[ORM\Column(nullable: true)]
    private ?float $actualDistanceMeters = null;

    #[ORM\Column(nullable: true)]
    private ?int $actualResistanceLevel = null;

    #[ORM\Column(nullable: true)]
    private ?float $rir = null;

    #[ORM\Column]
    private bool $reachedFailure = false;

    #[ORM\Column(enumType: PerceivedLoad::class, nullable: true)]
    private ?PerceivedLoad $perceivedLoad = null;

    #[ORM\Column(enumType: PerceivedEffort::class, nullable: true)]
    private ?PerceivedEffort $perceivedEffort = null;

    #[ORM\Column(nullable: true)]
    private ?int $restSecondsActual = null;

    #[ORM\Column]
    private bool $skipped = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $skipReason = null;

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

    public function getId(): ?int { return $this->id; }

    public function getExerciseSession(): ExerciseSession { return $this->exerciseSession; }

    public function setExerciseSession(ExerciseSession $exerciseSession): self
    {
        $this->exerciseSession = $exerciseSession;
        $this->touch();
        return $this;
    }

    public function getSetNumber(): int { return $this->setNumber; }

    public function setSetNumber(int $setNumber): self
    {
        if ($setNumber < 1) {
            throw new \InvalidArgumentException('Il numero serie deve essere maggiore di zero.');
        }
        $this->setNumber = $setNumber;
        $this->touch();
        return $this;
    }

    public function getTargetRepMin(): ?int { return $this->targetRepMin; }
    public function setTargetRepMin(?int $targetRepMin): self { $this->targetRepMin = $targetRepMin !== null && $targetRepMin > 0 ? $targetRepMin : null; $this->touch(); return $this; }
    public function getTargetRepMax(): ?int { return $this->targetRepMax; }
    public function setTargetRepMax(?int $targetRepMax): self { $this->targetRepMax = $targetRepMax !== null && $targetRepMax > 0 ? $targetRepMax : null; $this->touch(); return $this; }
    public function getTargetWeightKg(): ?float { return $this->targetWeightKg; }
    public function setTargetWeightKg(?float $targetWeightKg): self { $this->targetWeightKg = $targetWeightKg !== null && $targetWeightKg > 0 ? $targetWeightKg : null; $this->touch(); return $this; }
    public function getTargetDurationSeconds(): ?int { return $this->targetDurationSeconds; }
    public function setTargetDurationSeconds(?int $targetDurationSeconds): self { $this->targetDurationSeconds = $targetDurationSeconds !== null && $targetDurationSeconds > 0 ? $targetDurationSeconds : null; $this->touch(); return $this; }
    public function getRestSecondsPlanned(): ?int { return $this->restSecondsPlanned; }
    public function setRestSecondsPlanned(?int $restSecondsPlanned): self { $this->restSecondsPlanned = $restSecondsPlanned !== null && $restSecondsPlanned > 0 ? $restSecondsPlanned : null; $this->touch(); return $this; }
    public function getActualWeightKg(): ?float { return $this->actualWeightKg; }
    public function setActualWeightKg(?float $actualWeightKg): self { $this->actualWeightKg = $actualWeightKg !== null && $actualWeightKg >= 0 ? $actualWeightKg : null; $this->touch(); return $this; }
    public function getActualReps(): ?int { return $this->actualReps; }
    public function setActualReps(?int $actualReps): self { $this->actualReps = $actualReps !== null && $actualReps >= 0 ? $actualReps : null; $this->touch(); return $this; }
    public function getActualDurationSeconds(): ?int { return $this->actualDurationSeconds; }
    public function setActualDurationSeconds(?int $actualDurationSeconds): self { $this->actualDurationSeconds = $actualDurationSeconds !== null && $actualDurationSeconds > 0 ? $actualDurationSeconds : null; $this->touch(); return $this; }
    public function getActualDistanceMeters(): ?float { return $this->actualDistanceMeters; }
    public function setActualDistanceMeters(?float $actualDistanceMeters): self { $this->actualDistanceMeters = $actualDistanceMeters !== null && $actualDistanceMeters >= 0 ? $actualDistanceMeters : null; $this->touch(); return $this; }
    public function getActualResistanceLevel(): ?int { return $this->actualResistanceLevel; }
    public function setActualResistanceLevel(?int $actualResistanceLevel): self { $this->actualResistanceLevel = $actualResistanceLevel !== null && $actualResistanceLevel >= 0 ? $actualResistanceLevel : null; $this->touch(); return $this; }
    public function getRir(): ?float { return $this->rir; }
    public function setRir(?float $rir): self { $this->rir = $rir !== null && $rir >= 0 ? $rir : null; $this->touch(); return $this; }
    public function isReachedFailure(): bool { return $this->reachedFailure; }
    public function setReachedFailure(bool $reachedFailure): self { $this->reachedFailure = $reachedFailure; $this->touch(); return $this; }
    public function getPerceivedLoad(): ?PerceivedLoad { return $this->perceivedLoad; }
    public function setPerceivedLoad(?PerceivedLoad $perceivedLoad): self { $this->perceivedLoad = $perceivedLoad; $this->touch(); return $this; }
    public function getPerceivedEffort(): ?PerceivedEffort { return $this->perceivedEffort; }
    public function setPerceivedEffort(?PerceivedEffort $perceivedEffort): self { $this->perceivedEffort = $perceivedEffort; $this->touch(); return $this; }
    public function getRestSecondsActual(): ?int { return $this->restSecondsActual; }
    public function setRestSecondsActual(?int $restSecondsActual): self { $this->restSecondsActual = $restSecondsActual !== null && $restSecondsActual > 0 ? $restSecondsActual : null; $this->touch(); return $this; }
    public function isSkipped(): bool { return $this->skipped; }
    public function setSkipped(bool $skipped): self
    {
        $this->skipped = $skipped;
        if (!$skipped) {
            $this->skipReason = null;
        }
        $this->touch();
        return $this;
    }
    public function getSkipReason(): ?string { return $this->skipReason; }
    public function setSkipReason(?string $skipReason): self
    {
        $this->skipReason = $skipReason !== null ? trim($skipReason) : null;
        if ($this->skipReason === '') {
            $this->skipReason = null;
        }
        $this->touch();
        return $this;
    }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): self { $this->notes = $notes !== null ? trim($notes) : null; $this->touch(); return $this; }

    public function hasActualData(): bool
    {
        return $this->skipped
            || $this->actualWeightKg !== null
            || $this->actualReps !== null
            || $this->actualDurationSeconds !== null
            || $this->actualDistanceMeters !== null
            || $this->actualResistanceLevel !== null
            || $this->rir !== null
            || $this->reachedFailure
            || $this->perceivedLoad !== null
            || $this->perceivedEffort !== null
            || ($this->notes !== null && $this->notes !== '');
    }

    public function getTargetSummary(): string
    {
        $parts = [];
        if ($this->targetWeightKg !== null) {
            $parts[] = $this->formatWeight($this->targetWeightKg);
        }
        if ($this->targetRepMin !== null && $this->targetRepMax !== null) {
            $parts[] = sprintf('%d-%d reps', $this->targetRepMin, $this->targetRepMax);
        } elseif ($this->targetRepMin !== null) {
            $parts[] = sprintf('%d reps', $this->targetRepMin);
        }
        if ($this->targetDurationSeconds !== null) {
            $parts[] = $this->formatSeconds($this->targetDurationSeconds);
        }

        return $parts !== [] ? implode(' · ', $parts) : 'Libera';
    }

    public function getActualSummary(): string
    {
        if ($this->skipped) {
            return $this->skipReason !== null ? 'Saltata: ' . $this->skipReason : 'Saltata';
        }

        if (!$this->hasActualData()) {
            return 'Non registrata';
        }

        $parts = [];
        if ($this->actualWeightKg !== null) {
            $parts[] = $this->formatWeight($this->actualWeightKg);
        }
        if ($this->actualReps !== null) {
            $parts[] = sprintf('%d reps', $this->actualReps);
        }
        if ($this->actualDurationSeconds !== null) {
            $parts[] = $this->formatSeconds($this->actualDurationSeconds);
        }
        if ($this->actualDistanceMeters !== null) {
            $parts[] = sprintf('%s m', rtrim(rtrim(number_format($this->actualDistanceMeters, 1, ',', ''), '0'), ','));
        }
        if ($this->rir !== null) {
            $parts[] = sprintf('RIR %s', rtrim(rtrim(number_format($this->rir, 1, ',', ''), '0'), ','));
        }
        if ($this->reachedFailure && $this->rir === null) {
            $parts[] = 'cedimento';
        }

        return $parts !== [] ? implode(' · ', $parts) : 'Registrata';
    }

    private function formatWeight(float $weight): string
    {
        return rtrim(rtrim(number_format($weight, 1, ',', ''), '0'), ',') . ' kg';
    }

    private function formatSeconds(int $seconds): string
    {
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
