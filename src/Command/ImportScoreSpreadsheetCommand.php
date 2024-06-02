<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Question;
use App\Entity\Score;
use App\Entity\Student;
use App\Repository\ScoreRepository;
use App\Repository\StudentRepository;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'import:score-spreadsheet',
    description: 'Import Excel file with scores from /data/Assignment.xlsx',
)]
class ImportScoreSpreadsheetCommand extends Command
{

    private const EXCEL_FILE = '/data/Assignment.xlsx';

    private KernelInterface $appKernel;
    private QuestionRepository $questionRepository;
    private StudentRepository $studentRepository;
    private ScoreRepository $scoreRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        KernelInterface $appKernel,
        QuestionRepository $questionRepository,
        StudentRepository $studentRepository,
        ScoreRepository $scoreRepository,
        EntityManagerInterface $entityManager,
    )
    {
        $this->appKernel = $appKernel;
        $this->questionRepository = $questionRepository;
        $this->studentRepository = $studentRepository;
        $this->scoreRepository = $scoreRepository;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $spreadsheet = IOFactory::load($this->appKernel->getProjectDir() . $this::EXCEL_FILE);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true); // here, the read data is turned into an array
        $questionRow = array_shift($sheetData);
        $maxScoreRow = array_shift($sheetData);

        // Remove label cells.
        array_shift($questionRow);
        array_shift($maxScoreRow);

        $questionQueryBuilder = $this->questionRepository->createQueryBuilder('q');
        $studentQueryBuilder = $this->studentRepository->createQueryBuilder('st');
        $scoreQueryBuilder = $this->scoreRepository->createQueryBuilder('sc');

        //Clear existing data before import.
        $questionQueryBuilder->delete()->getQuery()->execute();
        $studentQueryBuilder->delete()->getQuery()->execute();
        $scoreQueryBuilder->delete()->getQuery()->execute();

        $questionIds = [];
        foreach ($questionRow as $colKey => &$questionItem) {
            $questionEntity = new Question();
            $questionEntity->setLabel($questionItem);
            $questionEntity->setMaxScore((int) $maxScoreRow[$colKey]);
            $this->entityManager->persist($questionEntity);
            $this->entityManager->flush();
            $questionIds[$colKey] = $questionEntity->getId();
        }

        foreach ($sheetData as $rowNumber => $rowData) {
            $studentEntity = new Student();
            $studentEntity->setLabel(array_shift($rowData));
            $this->entityManager->persist($studentEntity);
            $this->entityManager->flush();
            $studentId = $studentEntity->getId();
            foreach ($rowData as $colKey => $studentScore) {
                $scoreEntity = new Score();
                $scoreEntity->setValue((int) $studentScore);
                $scoreEntity->setStudentId((int) $studentId);
                $scoreEntity->setQuestionId((int) $questionIds[$colKey]);
                $this->entityManager->persist($scoreEntity);
            }
            $this->entityManager->flush();
        }

        $io->success('Excel file successfully imported.');

        return Command::SUCCESS;
    }
}
