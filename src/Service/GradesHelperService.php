<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\QuestionRepository;
use App\Repository\ScoreRepository;
use App\Repository\StudentRepository;

class GradesHelperService
{
    private QuestionRepository $questionRepository;
    private StudentRepository $studentRepository;
    private ScoreRepository $scoreRepository;

    public function __construct(
        QuestionRepository $questionRepository,
        StudentRepository $studentRepository,
        ScoreRepository $scoreRepository,
    )
    {
        $this->questionRepository = $questionRepository;
        $this->studentRepository = $studentRepository;
        $this->scoreRepository = $scoreRepository;
    }

    public function getAllGrades()
    {
        $studentQuery = $this->studentRepository->createQueryBuilder('st')
            ->select('st.label', 'st.id')
            ->indexBy('st', 'st.id')
            ->getQuery();
        $students = $studentQuery->execute();

        $scoreQuery = $this->scoreRepository->createQueryBuilder('sc')
            //->where('sc.student_id', $studentId)
            ->indexBy('sc', 'sc.student_id')
            ->select('SUM(sc.value) AS total', 'sc.student_id')
            ->groupBy('sc.student_id')
            ->getQuery();
        $totalScores = $scoreQuery->execute();

        $questionQuery = $this->questionRepository->createQueryBuilder('q')
            ->select('SUM(q.max_score) AS total')
            ->getQuery();
        $maxPoints = (int) $questionQuery->getSingleScalarResult();

        $gradesTable = [];
        $gradesTable['header'] = ['Student', 'Score', 'Pass/Fail'];
        foreach ($students as $studentId => $student)  {
            $totalPoints = $totalScores[$studentId]['total'];
            // Calculate caesura.
            $score = $totalPoints / $maxPoints * 10;
            $failOrPass = $score > 5.5? 'Pass' : 'Fail';
            $gradesTable['data'][$studentId] = [
                'name' => $student['label'],
                'score' => $score < 1 ? 1 : round($score, 1),
                'passFail' => $failOrPass,
            ];
        }

        return $gradesTable;

    }
}