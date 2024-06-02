<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AnalyticsHelperService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AnalyticsController extends AbstractController
{
    private AnalyticsHelperService $analyticsHelperService;

    public function __construct(AnalyticsHelperService $analyticsHelperService)
    {
        $this->analyticsHelperService = $analyticsHelperService;
    }


    #[Route('/analytics', name: 'app_analytics')]
    public function index(): Response
    {
        return $this->render('analytics/index.html.twig', [
            'pValueTable' => $this->analyticsHelperService->getPvalueTable(),
            //'ritValueTable' => $this->analyticsHelperService->getRitValueTable(),
        ]);
    }

}
