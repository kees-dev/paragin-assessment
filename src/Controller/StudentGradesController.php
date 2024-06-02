<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GradesHelperService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StudentGradesController extends AbstractController
{

    private GradesHelperService $gradesHelperService;

    public function __construct(
        GradesHelperService $gradesHelperService,
    )
    {
        $this->gradesHelperService = $gradesHelperService;
    }
    #[Route('/student-grades', name: 'app_student_grades')]
    public function index(): Response
    {
        return $this->render('student_grades/index.html.twig', [
            'controller_name' => 'StudentGradesController',
            'grades' => $this->gradesHelperService->getAllGrades(),
        ]);
    }
}
