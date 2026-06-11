<?php

namespace App\Dto;

final readonly class SetLoadAdjustment
{
    public function __construct(
        public string $status,
        public string $label,
        public string $badgeClass,
        public string $message,
        public ?float $suggestedWeightKg = null,
    ) {
    }

    public function hasSuggestedWeight(): bool
    {
        return $this->suggestedWeightKg !== null;
    }

    public function formattedSuggestedWeight(): ?string
    {
        if ($this->suggestedWeightKg === null) {
            return null;
        }

        return rtrim(rtrim(number_format($this->suggestedWeightKg, 1, ',', ''), '0'), ',') . ' kg';
    }
}
