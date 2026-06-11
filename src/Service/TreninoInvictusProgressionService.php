<?php

namespace App\Service;

final class TreninoInvictusProgressionService
{
    /**
     * @return list<array{repMin:int, repMax:int, setCount:int, restSeconds:int, zoneName:string}>
     */
    public function getStepBlocks(int $stepNumber): array
    {
        return match ($this->normalizeStepNumber($stepNumber)) {
            1 => [
                ['repMin' => 8, 'repMax' => 10, 'setCount' => 2, 'restSeconds' => 150, 'zoneName' => 'Metabolico-ipertrofico'],
                ['repMin' => 10, 'repMax' => 12, 'setCount' => 2, 'restSeconds' => 150, 'zoneName' => 'Metabolico'],
            ],
            2 => [
                ['repMin' => 6, 'repMax' => 8, 'setCount' => 1, 'restSeconds' => 150, 'zoneName' => 'Ipertrofico'],
                ['repMin' => 8, 'repMax' => 10, 'setCount' => 2, 'restSeconds' => 150, 'zoneName' => 'Metabolico-ipertrofico'],
                ['repMin' => 10, 'repMax' => 12, 'setCount' => 1, 'restSeconds' => 150, 'zoneName' => 'Metabolico'],
            ],
            3 => [
                ['repMin' => 6, 'repMax' => 8, 'setCount' => 2, 'restSeconds' => 150, 'zoneName' => 'Ipertrofico'],
                ['repMin' => 8, 'repMax' => 10, 'setCount' => 2, 'restSeconds' => 150, 'zoneName' => 'Metabolico-ipertrofico'],
            ],
            4 => [
                ['repMin' => 4, 'repMax' => 6, 'setCount' => 1, 'restSeconds' => 180, 'zoneName' => 'Forza-ipertrofico'],
                ['repMin' => 6, 'repMax' => 8, 'setCount' => 1, 'restSeconds' => 180, 'zoneName' => 'Ipertrofico'],
                ['repMin' => 8, 'repMax' => 10, 'setCount' => 2, 'restSeconds' => 180, 'zoneName' => 'Metabolico-ipertrofico'],
            ],
            5 => [
                ['repMin' => 4, 'repMax' => 6, 'setCount' => 1, 'restSeconds' => 180, 'zoneName' => 'Forza-ipertrofico'],
                ['repMin' => 6, 'repMax' => 8, 'setCount' => 2, 'restSeconds' => 180, 'zoneName' => 'Ipertrofico'],
                ['repMin' => 8, 'repMax' => 10, 'setCount' => 1, 'restSeconds' => 180, 'zoneName' => 'Metabolico-ipertrofico'],
            ],
            6 => [
                ['repMin' => 4, 'repMax' => 6, 'setCount' => 2, 'restSeconds' => 180, 'zoneName' => 'Forza-ipertrofico'],
                ['repMin' => 6, 'repMax' => 8, 'setCount' => 2, 'restSeconds' => 180, 'zoneName' => 'Ipertrofico'],
            ],
        };
    }

    public function getStepSummary(int $stepNumber): string
    {
        $parts = [];
        foreach ($this->getStepBlocks($stepNumber) as $block) {
            $parts[] = sprintf('%dx%d-%d', $block['setCount'], $block['repMin'], $block['repMax']);
        }

        return sprintf('Step %d: %s', $this->normalizeStepNumber($stepNumber), implode(' + ', $parts));
    }

    public function normalizeStepNumber(?int $stepNumber): int
    {
        if ($stepNumber === null || $stepNumber < 1) {
            return 1;
        }

        return min($stepNumber, 6);
    }
}
