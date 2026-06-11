<?php

namespace App\Entity;

use App\Enum\LoadProfileConfidence;
use App\Enum\LoadProfileSource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ExerciseLoadProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AppUser $appUser;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Exercise $exercise;

    #[ORM\Column(enumType: LoadProfileSource::class)]
    private LoadProfileSource $source = LoadProfileSource::InitialCalibration;

    #[ORM\Column(enumType: LoadProfileConfidence::class)]
    private LoadProfileConfidence $confidence = LoadProfileConfidence::Low;

    #[ORM\Column(nullable: true)]
    private ?float $estimatedOneRepMaxKg = null;

    #[ORM\Column]
    private \DateTimeImmutable $validFrom;

    /** @var Collection<int, ExerciseLoadRange> */
    #[ORM\OneToMany(mappedBy: 'exerciseLoadProfile', targetEntity: ExerciseLoadRange::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['repMin' => 'ASC'])]
    private Collection $ranges;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->ranges = new ArrayCollection();
        $this->validFrom = new \DateTimeImmutable();
        $this->createdAt = $this->validFrom;
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): ?int { return $this->id; }
    public function getAppUser(): AppUser { return $this->appUser; }
    public function setAppUser(AppUser $appUser): self { $this->appUser = $appUser; $this->touch(); return $this; }
    public function getExercise(): Exercise { return $this->exercise; }
    public function setExercise(Exercise $exercise): self { $this->exercise = $exercise; $this->touch(); return $this; }
    public function getSource(): LoadProfileSource { return $this->source; }
    public function setSource(LoadProfileSource $source): self { $this->source = $source; $this->touch(); return $this; }
    public function getConfidence(): LoadProfileConfidence { return $this->confidence; }
    public function setConfidence(LoadProfileConfidence $confidence): self { $this->confidence = $confidence; $this->touch(); return $this; }
    public function getEstimatedOneRepMaxKg(): ?float { return $this->estimatedOneRepMaxKg; }
    public function setEstimatedOneRepMaxKg(?float $estimatedOneRepMaxKg): self { $this->estimatedOneRepMaxKg = $estimatedOneRepMaxKg !== null && $estimatedOneRepMaxKg > 0 ? $estimatedOneRepMaxKg : null; $this->touch(); return $this; }
    public function getValidFrom(): \DateTimeImmutable { return $this->validFrom; }
    public function setValidFrom(\DateTimeImmutable $validFrom): self { $this->validFrom = $validFrom; $this->touch(); return $this; }

    /** @return Collection<int, ExerciseLoadRange> */
    public function getRanges(): Collection { return $this->ranges; }

    public function addRange(ExerciseLoadRange $range): self
    {
        if (!$this->ranges->contains($range)) {
            $this->ranges->add($range);
            $range->setExerciseLoadProfile($this);
            $this->touch();
        }

        return $this;
    }


    public function getSuggestedWeightForRepRange(int $repMin, int $repMax): ?float
    {
        $bestRange = null;
        $bestScore = PHP_INT_MAX;

        foreach ($this->ranges as $range) {
            if ($range->containsRepRange($repMin, $repMax)) {
                return $range->getSuggestedWeightKg();
            }

            $score = abs($range->getRepMin() - $repMin) + abs($range->getRepMax() - $repMax);
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestRange = $range;
            }
        }

        return $bestRange?->getSuggestedWeightKg();
    }

    public function getRangeSummary(): string
    {
        if ($this->ranges->isEmpty()) {
            return 'Nessun range calcolato';
        }

        $parts = [];
        foreach ($this->ranges as $range) {
            $parts[] = sprintf('%d-%d: %s kg', $range->getRepMin(), $range->getRepMax(), rtrim(rtrim(number_format($range->getSuggestedWeightKg(), 1, ',', ''), '0'), ','));
        }

        return implode(' · ', $parts);
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
