<?php

namespace App\Service;

use App\Entity\AppUser;
use App\Entity\Exercise;
use App\Entity\ExerciseSession;
use App\Entity\SetLog;
use App\Entity\WorkoutSession;
use App\Enum\ProgressionType;
use App\Enum\WorkoutSessionType;

final class CalibrationSessionFactory
{
    public function createForExercise(AppUser $appUser, Exercise $exercise): WorkoutSession
    {
        $session = new WorkoutSession();
        $session
            ->setAppUser($appUser)
            ->setSessionType(WorkoutSessionType::Calibration)
            ->setNotes('Sessione dedicata alla calibrazione iniziale dei carichi.');

        $exerciseSession = new ExerciseSession();
        $exerciseSession
            ->setExercise($exercise)
            ->setPosition(1)
            ->setProgressionType(ProgressionType::Manual)
            ->setNotes('Serie test progressive: registra kg, reps, RIR e sensazioni. Non serve andare a cedimento.');

        foreach ([1, 2, 3, 4] as $setNumber) {
            $setLog = new SetLog();
            $setLog
                ->setSetNumber($setNumber)
                ->setTargetRepMin(8)
                ->setTargetRepMax(10)
                ->setRestSecondsPlanned($setNumber <= 2 ? 150 : 180);
            $exerciseSession->addSetLog($setLog);
        }

        $session->addExerciseSession($exerciseSession);

        return $session;
    }
}
