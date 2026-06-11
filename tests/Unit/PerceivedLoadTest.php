<?php

namespace App\Tests\Unit;

use App\Enum\PerceivedLoad;
use PHPUnit\Framework\TestCase;

final class PerceivedLoadTest extends TestCase
{
    public function testSelectableCasesDoNotExposeFailureBecauseFailureIsHandledByRirZero(): void
    {
        self::assertContains(PerceivedLoad::TooLight, PerceivedLoad::selectableCases());
        self::assertContains(PerceivedLoad::Correct, PerceivedLoad::selectableCases());
        self::assertContains(PerceivedLoad::HeavyButOk, PerceivedLoad::selectableCases());
        self::assertContains(PerceivedLoad::TooHeavy, PerceivedLoad::selectableCases());
        self::assertNotContains(PerceivedLoad::Failure, PerceivedLoad::selectableCases());
    }
}
