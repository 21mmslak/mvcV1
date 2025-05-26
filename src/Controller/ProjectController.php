<?php

namespace App\Controller;

use App\Project\Data;
use App\Project\StartBlackJack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class ProjectController extends AbstractController
{
    // private Data $data;

    // public function __construct(Data $data)
    // {
    //     $this->data = $data;
    // }

    private function getData(SessionInterface $session): Data
    {
        return new Data($session);
    }

    #[Route("/proj", name: "proj")]
    public function proj(): Response
    {
        return $this->render('project/index.html.twig');
    }

    #[Route("/proj_main", name: "proj_main")]
    public function projMain(SessionInterface $session): Response
    {
        $data = $this->getData($session);
        
        if (!$data->get('game_started'))
        {
            $start = new StartBlackJack();
            $start->start($data);
        }

        return $this->render('project/game.html.twig', [
            'data' => $data->getAll(),
        ]);
    }
}