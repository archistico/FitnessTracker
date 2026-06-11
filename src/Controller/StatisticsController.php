<?php

namespace App\Controller;

use App\Entity\Exercise;
use App\Entity\SetLog;
use App\Entity\WorkoutSession;
use App\Enum\WorkoutSessionType;
use App\Service\CurrentUserProvider;
use App\Service\ExerciseTrendBuilder;
use App\Service\ExerciseWeeklySummaryBuilder;
use App\Service\EstimatedStrengthCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatisticsController extends AbstractController
{
    #[Route('/statistics', name: 'app_statistics_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider, EstimatedStrengthCalculator $estimatedStrengthCalculator): Response
    {
        $currentUser = $currentUserProvider->getUser();
        $filters = $this->buildFilters($request);

        /** @var list<WorkoutSession> $sessions */
        $sessions = $entityManager->getRepository(WorkoutSession::class)->findBy(
            ['appUser' => $currentUser],
            ['sessionDate' => 'DESC', 'id' => 'DESC']
        );
        $sessions = $this->filterSessions($sessions, $filters);

        $exerciseStats = [];
        $recentRows = [];
        $totalCompletedSets = 0;
        $totalReps = 0;
        $totalVolume = 0.0;
        $rirSum = 0.0;
        $rirCount = 0;
        $completedSessionIds = [];

        foreach ($sessions as $session) {
            foreach ($session->getExerciseSessions() as $exerciseSession) {
                $exercise = $exerciseSession->getExercise();
                $exerciseId = $exercise->getId();
                if ($exerciseId === null) {
                    continue;
                }

                if (!isset($exerciseStats[$exerciseId])) {
                    $exerciseStats[$exerciseId] = $this->createEmptyExerciseStats($exercise);
                }

                foreach ($exerciseSession->getSetLogs() as $setLog) {
                    if ($setLog->isSkipped() || !$setLog->hasActualData()) {
                        continue;
                    }

                    ++$totalCompletedSets;
                    $completedSessionIds[$session->getId() ?? spl_object_id($session)] = true;
                    $exerciseStats[$exerciseId]['completedSets']++;

                    if ($setLog->getActualReps() !== null) {
                        $totalReps += $setLog->getActualReps();
                        $exerciseStats[$exerciseId]['totalReps'] += $setLog->getActualReps();
                    }

                    $volume = $this->calculateVolume($setLog);
                    if ($volume > 0) {
                        $totalVolume += $volume;
                        $exerciseStats[$exerciseId]['totalVolume'] += $volume;
                    }

                    $estimatedStrengthKg = $estimatedStrengthCalculator->estimateOneRepMax(
                        $setLog->getActualWeightKg(),
                        $setLog->getActualReps(),
                        $setLog->getRir()
                    );
                    if ($estimatedStrengthKg !== null && (
                        $exerciseStats[$exerciseId]['bestEstimatedStrengthKg'] === null
                        || $estimatedStrengthKg > $exerciseStats[$exerciseId]['bestEstimatedStrengthKg']
                    )) {
                        $exerciseStats[$exerciseId]['bestEstimatedStrengthKg'] = $estimatedStrengthKg;
                        $exerciseStats[$exerciseId]['bestEstimatedSetSummary'] = $setLog->getActualSummary();
                    }

                    if ($setLog->getRir() !== null) {
                        $rirSum += $setLog->getRir();
                        ++$rirCount;
                        $exerciseStats[$exerciseId]['rirSum'] += $setLog->getRir();
                        $exerciseStats[$exerciseId]['rirCount']++;
                    }

                    if ($setLog->getActualWeightKg() !== null) {
                        $exerciseStats[$exerciseId]['lastWeightKg'] ??= $setLog->getActualWeightKg();
                        $exerciseStats[$exerciseId]['bestWeightKg'] = max(
                            $exerciseStats[$exerciseId]['bestWeightKg'] ?? 0,
                            $setLog->getActualWeightKg()
                        );
                    }

                    $exerciseStats[$exerciseId]['lastSessionDate'] ??= $session->getSessionDate();
                    $exerciseStats[$exerciseId]['sessionIds'][$session->getId() ?? spl_object_id($session)] = true;

                    $recentRows[] = [
                        'date' => $session->getSessionDate(),
                        'sessionId' => $session->getId(),
                        'sessionType' => $session->getSessionType()->label(),
                        'exerciseName' => $exercise->getName(),
                        'setNumber' => $setLog->getSetNumber(),
                        'summary' => $setLog->getActualSummary(),
                        'volume' => $volume,
                        'estimatedStrengthKg' => $estimatedStrengthKg,
                        'rir' => $setLog->getRir(),
                    ];
                }
            }
        }

        foreach ($exerciseStats as &$stats) {
            $stats['sessionCount'] = count($stats['sessionIds']);
            $stats['averageRir'] = $stats['rirCount'] > 0 ? $stats['rirSum'] / $stats['rirCount'] : null;
            unset($stats['sessionIds'], $stats['rirSum'], $stats['rirCount']);
        }
        unset($stats);

        usort($exerciseStats, static function (array $a, array $b): int {
            $volumeCompare = $b['totalVolume'] <=> $a['totalVolume'];
            if ($volumeCompare !== 0) {
                return $volumeCompare;
            }

            $setCompare = $b['completedSets'] <=> $a['completedSets'];
            if ($setCompare !== 0) {
                return $setCompare;
            }

            return $a['exerciseName'] <=> $b['exerciseName'];
        });

        usort($recentRows, static function (array $a, array $b): int {
            return $b['date']->getTimestamp() <=> $a['date']->getTimestamp();
        });

        return $this->render('statistics/index.html.twig', [
            'currentUser' => $currentUser,
            'filters' => $filters,
            'filterQuery' => $this->buildFilterQuery($filters),
            'sessionTypes' => WorkoutSessionType::cases(),
            'summary' => [
                'completedSessionCount' => count($completedSessionIds),
                'completedSets' => $totalCompletedSets,
                'totalReps' => $totalReps,
                'totalVolume' => $totalVolume,
                'averageRir' => $rirCount > 0 ? $rirSum / $rirCount : null,
                'exerciseCount' => count($exerciseStats),
            ],
            'exerciseStats' => array_slice($exerciseStats, 0, 50),
            'recentRows' => array_slice($recentRows, 0, 20),
        ]);
    }

    #[Route('/statistics/exercises/{slug}', name: 'app_statistics_exercise', methods: ['GET'])]
    public function exercise(string $slug, Request $request, EntityManagerInterface $entityManager, CurrentUserProvider $currentUserProvider, ExerciseTrendBuilder $exerciseTrendBuilder, ExerciseWeeklySummaryBuilder $exerciseWeeklySummaryBuilder, EstimatedStrengthCalculator $estimatedStrengthCalculator): Response
    {
        $currentUser = $currentUserProvider->getUser();
        $filters = $this->buildFilters($request);

        $exercise = $entityManager->getRepository(Exercise::class)->findOneBy(['slug' => $slug]);
        if (!$exercise instanceof Exercise) {
            throw $this->createNotFoundException('Esercizio non trovato.');
        }

        /** @var list<WorkoutSession> $sessions */
        $sessions = $entityManager->getRepository(WorkoutSession::class)->findBy(
            ['appUser' => $currentUser],
            ['sessionDate' => 'DESC', 'id' => 'DESC']
        );
        $sessions = $this->filterSessions($sessions, $filters);

        $rows = [];
        $sessionSummariesByKey = [];
        $summary = [
            'sessionCount' => 0,
            'completedSets' => 0,
            'totalReps' => 0,
            'totalVolume' => 0.0,
            'totalDurationSeconds' => 0,
            'totalDistanceMeters' => 0.0,
            'bestWeightKg' => null,
            'lastWeightKg' => null,
            'bestEstimatedStrengthKg' => null,
            'bestEstimatedSetSummary' => null,
            'averageRir' => null,
        ];

        $rirSum = 0.0;
        $rirCount = 0;

        foreach ($sessions as $session) {
            $sessionKey = (string) ($session->getId() ?? spl_object_id($session));

            foreach ($session->getExerciseSessions() as $exerciseSession) {
                if ($exerciseSession->getExercise()->getId() !== $exercise->getId()) {
                    continue;
                }

                foreach ($exerciseSession->getSetLogs() as $setLog) {
                    if ($setLog->isSkipped() || !$setLog->hasActualData()) {
                        continue;
                    }

                    $volume = $this->calculateVolume($setLog);

                    $summary['completedSets']++;
                    $summary['totalVolume'] += $volume;

                    $estimatedStrengthKg = $estimatedStrengthCalculator->estimateOneRepMax(
                        $setLog->getActualWeightKg(),
                        $setLog->getActualReps(),
                        $setLog->getRir()
                    );
                    if ($estimatedStrengthKg !== null && (
                        $summary['bestEstimatedStrengthKg'] === null
                        || $estimatedStrengthKg > $summary['bestEstimatedStrengthKg']
                    )) {
                        $summary['bestEstimatedStrengthKg'] = $estimatedStrengthKg;
                        $summary['bestEstimatedSetSummary'] = $setLog->getActualSummary();
                    }

                    if ($setLog->getActualReps() !== null) {
                        $summary['totalReps'] += $setLog->getActualReps();
                    }

                    if ($setLog->getActualDurationSeconds() !== null) {
                        $summary['totalDurationSeconds'] += $setLog->getActualDurationSeconds();
                    }

                    if ($setLog->getActualDistanceMeters() !== null) {
                        $summary['totalDistanceMeters'] += $setLog->getActualDistanceMeters();
                    }

                    if ($setLog->getActualWeightKg() !== null) {
                        $summary['lastWeightKg'] ??= $setLog->getActualWeightKg();
                        $summary['bestWeightKg'] = max($summary['bestWeightKg'] ?? 0, $setLog->getActualWeightKg());
                    }

                    if ($setLog->getRir() !== null) {
                        $rirSum += $setLog->getRir();
                        ++$rirCount;
                    }

                    if (!isset($sessionSummariesByKey[$sessionKey])) {
                        $sessionSummariesByKey[$sessionKey] = [
                            'date' => $session->getSessionDate(),
                            'sessionId' => $session->getId(),
                            'planName' => $session->getWorkoutPlan()?->getName() ?? 'Allenamento libero',
                            'sessionType' => $session->getSessionType()->label(),
                            'setCount' => 0,
                            'totalReps' => 0,
                            'totalVolume' => 0.0,
                            'bestWeightKg' => null,
                            'bestEstimatedStrengthKg' => null,
                            'bestEstimatedSetSummary' => null,
                            'rirSum' => 0.0,
                            'rirCount' => 0,
                            'averageRir' => null,
                        ];
                    }

                    $sessionSummariesByKey[$sessionKey]['setCount']++;
                    $sessionSummariesByKey[$sessionKey]['totalVolume'] += $volume;

                    if ($setLog->getActualReps() !== null) {
                        $sessionSummariesByKey[$sessionKey]['totalReps'] += $setLog->getActualReps();
                    }

                    if ($setLog->getActualWeightKg() !== null) {
                        $sessionSummariesByKey[$sessionKey]['bestWeightKg'] = max(
                            $sessionSummariesByKey[$sessionKey]['bestWeightKg'] ?? 0,
                            $setLog->getActualWeightKg()
                        );
                    }

                    if ($estimatedStrengthKg !== null && (
                        $sessionSummariesByKey[$sessionKey]['bestEstimatedStrengthKg'] === null
                        || $estimatedStrengthKg > $sessionSummariesByKey[$sessionKey]['bestEstimatedStrengthKg']
                    )) {
                        $sessionSummariesByKey[$sessionKey]['bestEstimatedStrengthKg'] = $estimatedStrengthKg;
                        $sessionSummariesByKey[$sessionKey]['bestEstimatedSetSummary'] = $setLog->getActualSummary();
                    }

                    if ($setLog->getRir() !== null) {
                        $sessionSummariesByKey[$sessionKey]['rirSum'] += $setLog->getRir();
                        $sessionSummariesByKey[$sessionKey]['rirCount']++;
                    }

                    $rows[] = [
                        'date' => $session->getSessionDate(),
                        'sessionId' => $session->getId(),
                        'planName' => $session->getWorkoutPlan()?->getName() ?? 'Allenamento libero',
                        'sessionType' => $session->getSessionType()->label(),
                        'setNumber' => $setLog->getSetNumber(),
                        'targetSummary' => $setLog->getTargetSummary(),
                        'actualSummary' => $setLog->getActualSummary(),
                        'actualWeightKg' => $setLog->getActualWeightKg(),
                        'actualReps' => $setLog->getActualReps(),
                        'actualDurationSeconds' => $setLog->getActualDurationSeconds(),
                        'actualDistanceMeters' => $setLog->getActualDistanceMeters(),
                        'actualResistanceLevel' => $setLog->getActualResistanceLevel(),
                        'restSecondsActual' => $setLog->getRestSecondsActual(),
                        'volume' => $volume,
                        'estimatedStrengthKg' => $estimatedStrengthKg,
                        'rir' => $setLog->getRir(),
                        'perceivedLoad' => $setLog->getPerceivedLoad()?->label(),
                        'perceivedEffort' => $setLog->getPerceivedEffort()?->label(),
                        'notes' => $setLog->getNotes(),
                    ];
                }
            }
        }

        $sessionSummaries = array_values($sessionSummariesByKey);
        foreach ($sessionSummaries as &$sessionSummary) {
            $sessionSummary['averageRir'] = $sessionSummary['rirCount'] > 0
                ? $sessionSummary['rirSum'] / $sessionSummary['rirCount']
                : null;
            unset($sessionSummary['rirSum'], $sessionSummary['rirCount']);
        }
        unset($sessionSummary);

        usort($sessionSummaries, static function (array $a, array $b): int {
            return $b['date']->getTimestamp() <=> $a['date']->getTimestamp();
        });

        usort($rows, static function (array $a, array $b): int {
            $dateCompare = $b['date']->getTimestamp() <=> $a['date']->getTimestamp();
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            return $a['setNumber'] <=> $b['setNumber'];
        });

        $summary['sessionCount'] = count($sessionSummaries);
        $summary['averageRir'] = $rirCount > 0 ? $rirSum / $rirCount : null;

        return $this->render('statistics/exercise.html.twig', [
            'currentUser' => $currentUser,
            'exercise' => $exercise,
            'filters' => $filters,
            'filterQuery' => $this->buildFilterQuery($filters),
            'sessionTypes' => WorkoutSessionType::cases(),
            'summary' => $summary,
            'sessionSummaries' => $sessionSummaries,
            'trend' => $exerciseTrendBuilder->build($sessionSummaries),
            'weeklyTrend' => $exerciseWeeklySummaryBuilder->build($sessionSummaries),
            'rows' => $rows,
        ]);
    }

    /** @return array<string,mixed> */
    private function createEmptyExerciseStats(Exercise $exercise): array
    {
        return [
            'exerciseId' => $exercise->getId(),
            'exerciseName' => $exercise->getName(),
            'exerciseSlug' => $exercise->getSlug(),
            'trackingMode' => $exercise->getTrackingMode()->label(),
            'completedSets' => 0,
            'totalReps' => 0,
            'totalVolume' => 0.0,
            'bestWeightKg' => null,
            'lastWeightKg' => null,
            'bestEstimatedStrengthKg' => null,
            'bestEstimatedSetSummary' => null,
            'lastSessionDate' => null,
            'sessionIds' => [],
            'rirSum' => 0.0,
            'rirCount' => 0,
        ];
    }

    private function calculateVolume(SetLog $setLog): float
    {
        if ($setLog->getActualWeightKg() === null || $setLog->getActualReps() === null) {
            return 0.0;
        }

        return $setLog->getActualWeightKg() * $setLog->getActualReps();
    }

    /** @return array{dateFromInput:string,dateToInput:string,sessionTypeInput:string,dateFrom:?\DateTimeImmutable,dateTo:?\DateTimeImmutable,sessionType:?WorkoutSessionType} */
    private function buildFilters(Request $request): array
    {
        $dateFromInput = trim((string) $request->query->get('dateFrom', ''));
        $dateToInput = trim((string) $request->query->get('dateTo', ''));
        $sessionTypeInput = trim((string) $request->query->get('sessionType', ''));

        return [
            'dateFromInput' => $dateFromInput,
            'dateToInput' => $dateToInput,
            'sessionTypeInput' => $sessionTypeInput,
            'dateFrom' => $this->parseDate($dateFromInput),
            'dateTo' => $this->parseDate($dateToInput),
            'sessionType' => $sessionTypeInput !== '' ? WorkoutSessionType::tryFrom($sessionTypeInput) : null,
        ];
    }

    /** @param array{dateFromInput:string,dateToInput:string,sessionTypeInput:string,dateFrom:?\DateTimeImmutable,dateTo:?\DateTimeImmutable,sessionType:?WorkoutSessionType} $filters */
    private function buildFilterQuery(array $filters): array
    {
        $query = [];
        if ($filters['dateFromInput'] !== '') {
            $query['dateFrom'] = $filters['dateFromInput'];
        }

        if ($filters['dateToInput'] !== '') {
            $query['dateTo'] = $filters['dateToInput'];
        }

        if ($filters['sessionTypeInput'] !== '') {
            $query['sessionType'] = $filters['sessionTypeInput'];
        }

        return $query;
    }

    /** @param list<WorkoutSession> $sessions
     *  @param array{dateFromInput:string,dateToInput:string,sessionTypeInput:string,dateFrom:?\DateTimeImmutable,dateTo:?\DateTimeImmutable,sessionType:?WorkoutSessionType} $filters
     *  @return list<WorkoutSession>
     */
    private function filterSessions(array $sessions, array $filters): array
    {
        $filtered = [];

        foreach ($sessions as $session) {
            $sessionDate = $session->getSessionDate();

            if ($filters['dateFrom'] instanceof \DateTimeImmutable && $sessionDate < $filters['dateFrom']) {
                continue;
            }

            if ($filters['dateTo'] instanceof \DateTimeImmutable && $sessionDate > $filters['dateTo']->setTime(23, 59, 59)) {
                continue;
            }

            if ($filters['sessionType'] instanceof WorkoutSessionType && $session->getSessionType() !== $filters['sessionType']) {
                continue;
            }

            $filtered[] = $session;
        }

        return $filtered;
    }

    private function parseDate(string $value): ?\DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof \DateTimeImmutable ? $date : null;
    }
}
