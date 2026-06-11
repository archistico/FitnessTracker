<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ExerciseLoadRange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ranges')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ExerciseLoadProfile $exerciseLoadProfile;

    #[ORM\Column]
    private int $repMin;

    #[ORM\Column]
    private int $repMax;

    #[ORM\Column]
    private float $suggestedWeightKg;

    #[ORM\Column]
    private float $targetRirMin = 1.0;

    #[ORM\Column]
    private float $targetRirMax = 2.0;

    public function __construct(int $repMin = 1, int $repMax = 1, float $suggestedWeightKg = 0.0)
    {
        $this->setRepRange($repMin, $repMax);
        $this->setSuggestedWeightKg($suggestedWeightKg);
    }

    public function getId(): ?int { return $this->id; }
    public function getExerciseLoadProfile(): ExerciseLoadProfile { return $this->exerciseLoadProfile; }
    public function setExerciseLoadProfile(ExerciseLoadProfile $exerciseLoadProfile): self { $this->exerciseLoadProfile = $exerciseLoadProfile; return $this; }
    public function getRepMin(): int { return $this->repMin; }
    public function getRepMax(): int { return $this->repMax; }

    public function setRepRange(int $repMin, int $repMax): self
    {
        if ($repMin < 1 || $repMax < $repMin) {
            throw new \InvalidArgumentException('Il range reps del profilo carico non è valido.');
        }

        $this->repMin = $repMin;
        $this->repMax = $repMax;

        return $this;
    }

    public function containsRepRange(int $repMin, int $repMax): bool
    {
        return $this->repMin <= $repMin && $this->repMax >= $repMax;
    }

    public function getSuggestedWeightKg(): float { return $this->suggestedWeightKg; }
    public function setSuggestedWeightKg(float $suggestedWeightKg): self { $this->suggestedWeightKg = max(0.0, $suggestedWeightKg); return $this; }
    public function getTargetRirMin(): float { return $this->targetRirMin; }
    public function setTargetRirMin(float $targetRirMin): self { $this->targetRirMin = max(0.0, $targetRirMin); return $this; }
    public function getTargetRirMax(): float { return $this->targetRirMax; }
    public function setTargetRirMax(float $targetRirMax): self { $this->targetRirMax = max($this->targetRirMin, $targetRirMax); return $this; }
}
