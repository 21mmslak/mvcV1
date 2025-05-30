<?php

namespace App\Controller;

use App\Repository\ScoreboardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ScoreboardController extends AbstractController
{
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route('/scoreboard', name: 'scoreboard')]
    public function index(ScoreboardRepository $scoreboardRepository): Response
    {
        $scoreboards = $scoreboardRepository->findBy([], ['coins' => 'DESC']);

        return $this->render('scoreboard/index.html.twig', [
            'scoreboards' => $scoreboards,
        ]);
    }
}