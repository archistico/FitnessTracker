<?php

namespace App\Dto;

final readonly class SetWeightSuggestion
{
    public function __construct(
        public ?float $weightKg,
        public string $sourceLabel,
        public string $helpText,
    ) {
    }

    public function hasWeight(): bool
    {
        return $this->weightKg !== null;
    }

    public function formattedWeight(): ?string
    {
        if ($this->weightKg === null) {
            return null;
        }

        return rtrim(rtrim(number_format($this->weightKg, 1, ',', ''), '0'), ',') . ' kg';
    }
}
