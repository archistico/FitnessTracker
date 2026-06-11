<?php

namespace App\Tests\Unit;

use App\Entity\ExerciseLoadProfile;
use App\Entity\ExerciseLoadRange;
use PHPUnit\Framework\TestCase;

final class ExerciseLoadProfileSuggestionTest extends TestCase
{
    public function testExactRangeSuggestionIsReturned(): void
    {
        $profile = new ExerciseLoadProfile();
        $profile->addRange(new ExerciseLoadRange(8, 10, 80.0));
        $profile->addRange(new ExerciseLoadRange(10, 12, 72.5));

        self::assertSame(80.0, $profile->getSuggestedWeightForRepRange(8, 10));
        self::assertSame(72.5, $profile->getSuggestedWeightForRepRange(10, 12));
    }

    public function testClosestRangeIsUsedWhenThereIsNoExactContainment(): void
    {
        $profile = new ExerciseLoadProfile();
        $profile->addRange(new ExerciseLoadRange(6, 8, 87.5));
        $profile->addRange(new ExerciseLoadRange(10, 12, 72.5));

        self::assertSame(87.5, $profile->getSuggestedWeightForRepRange(7, 9));
    }
}
