<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\QuestionRepository;
use App\Repository\ScoreRepository;
use App\Repository\StudentRepository;

class AnalyticsHelperService
{
    private QuestionRepository $questionRepository;
    private StudentRepository $studentRepository;
    private ScoreRepository $scoreRepository;
    private GradesHelperService $gradesHelperService;

    public function __construct(
        QuestionRepository $questionRepository,
        StudentRepository $studentRepository,
        ScoreRepository $scoreRepository,
        GradesHelperService $gradesHelperService,
    )
    {
        $this->questionRepository = $questionRepository;
        $this->studentRepository = $studentRepository;
        $this->scoreRepository = $scoreRepository;
        $this->gradesHelperService = $gradesHelperService;
    }

    /**
     * @return array
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getPvalueTable(): array
    {

        $scoreQuery = $this->scoreRepository->createQueryBuilder('sc')
            ->indexBy('sc', 'sc.question_id')
            ->select('AVG(sc.value) AS average', 'sc.question_id')
            ->groupBy('sc.question_id')
            ->getQuery();
        $averageScores = $scoreQuery->execute();

        $questionQuery = $this->questionRepository->createQueryBuilder('q')
            ->indexBy('q', 'q.id')
            ->select('q.max_score', 'q.id', 'q.label')
            ->getQuery();
        $questions = $questionQuery->execute();

        $pValueTable = [];
        $pValueTable['header'] = ['Question', 'P’-value'];
        foreach ($questions as $questionId => $question)  {
            $pValue = $averageScores[$questionId]['average'] / $question['max_score'];
            $pValueTable['data'][$questionId] = [
                'question' => $question['label'],
                'pValue' => $pValue,
            ];
        }

        return $pValueTable;
    }

    /**
     * @return array
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getRitValueTable(): array
    {
        // 'i' part, question scores
        $scoreQuery = $this->scoreRepository->createQueryBuilder('sc')
            ->select('sc.value', 'sc.question_id', 'sc.student_id')
            ->getQuery();
        $questionScores = $scoreQuery->execute();

        $scoresGroupedByQuestion = [];
        foreach ($questionScores as $questionScore) {
            $scoresGroupedByQuestion[$questionScore['question_id']][] = $questionScore['value'];
        }

        $questionQuery = $this->questionRepository->createQueryBuilder('q')
            ->indexBy('q', 'q.id')
            ->select('q.id', 'q.label')
            ->getQuery();
        $questions = $questionQuery->execute();

        // 't' part, test scores.
        $totalScores = $this->gradesHelperService->getTotalScores();
        $totalScoreValues = [];
        foreach ($totalScores as $totalScore) {
            $totalScoreValues[] = $totalScore['total'];
        }

        $ritValueTable = [];
        $ritValueTable['header'] = ['Question', 'rit-value'];
        foreach ($scoresGroupedByQuestion as $questionId => $scoreGroup) {
            // This cant work, since "i" is based on number of questions and "t" on number of students,
            // the lists to correlate are not of the same length.
            $ritValue = $this->getCorrelationCoefficent($scoreGroup, $totalScoreValues);
            $ritValueTable['data'][$questionId] = [
                'question' => $questions[$questionId]['label'],
                'ritValue' => $ritValue,
            ];
        }

        return $ritValueTable;
    }

    /**
     * Source from: https://gist.github.com/guyb7/7ff4436bf5b3b62eaaa1
     * @return float|int
     */
    private function getCorrelationCoefficent (array $a, array $b)
    {
        $sum_ab = 0;
        $sum_a = 0;
        $sum_b = 0;
        $sum_a_sqr = 0;
        $sum_b_sqr = 0;
        $n = min(array(count($a), count($b)));
        for ($i = 0; $i < $n; $i++) {
            if (!isset($a[$i]) || !isset($b[$i])) { continue; }
            $sum_ab += $a[$i] * $b[$i];
            $sum_a += $a[$i];
            $sum_b += $b[$i];
            $sum_a_sqr += pow($a[$i], 2);
            $sum_b_sqr += pow($b[$i], 2);
        }
        return ($sum_ab/$n - $sum_a/$n * $sum_b/$n) / (sqrt($sum_a_sqr/$n - pow($sum_a/$n, 2))
                * sqrt($sum_b_sqr/$n - pow($sum_b/$n, 2)));
    }

}