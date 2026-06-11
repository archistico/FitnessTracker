<?php

namespace App\Tests\Unit;

use App\Entity\SetLog;
use App\Enum\PerceivedLoad;
use PHPUnit\Framework\TestCase;

final class SetLogTest extends TestCase
{
    public function testHasActualDataDetectsInsertedPerformance(): void
    {
        $setLog = new SetLog();
        self::assertFalse($setLog->hasActualData());

        $setLog->setActualWeightKg(80)->setActualReps(8)->setRir(1);

        self::assertTrue($setLog->hasActualData());
        self::assertSame('80 kg · 8 reps · RIR 1', $setLog->getActualSummary());
    }

    public function testPerceivedLoadCountsAsActualData(): void
    {
        $setLog = (new SetLog())->setPerceivedLoad(PerceivedLoad::TooHeavy);

        self::assertTrue($setLog->hasActualData());
        self::assertSame(PerceivedLoad::TooHeavy, $setLog->getPerceivedLoad());
    }

    public function testSkippedSetCountsAsClosedButHasClearSummary(): void
    {
        $setLog = (new SetLog())->setSkipped(true)->setSkipReason('Poco tempo');

        self::assertTrue($setLog->isSkipped());
        self::assertTrue($setLog->hasActualData());
        self::assertSame('Saltata: Poco tempo', $setLog->getActualSummary());

        $setLog->setSkipped(false);

        self::assertFalse($setLog->isSkipped());
        self::assertNull($setLog->getSkipReason());
    }

    public function testRirZeroAlreadyRepresentsFailureWithoutDuplicatingSummary(): void
    {
        $setLog = (new SetLog())
            ->setActualWeightKg(80)
            ->setActualReps(8)
            ->setRir(0)
            ->setReachedFailure(true);

        self::assertSame('80 kg · 8 reps · RIR 0', $setLog->getActualSummary());
    }

}

