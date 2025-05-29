<?php

namespace App\Controller;

use App\Project\AddCardDealer;
use App\Project\AddCardPlayer;
use App\Project\Data;
use App\Project\DecideWinner;
use App\Project\StartBlackJack;
use App\Project\Split;
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

    #[Route("/add_card", name: "add_card")]
    public function addCardControll(SessionInterface $session): Response
    {
        $data = $this->getData($session);

        $add = new AddCardPlayer();
        $add->addCard($data);

        $game_status = $data->get('game_status');
        if (!$game_status)
        {
            $addDealer = new AddCardDealer();
            $addDealer->addCardDealer($data);

            $winner = new DecideWinner();
            $winner->decideWinner($data);

            return $this->render('project/winner.html.twig', [
                'data' => $data->getAll(),
            ]);
        }
        
        return $this->render('project/game.html.twig', [
            'data' => $data->getAll(),
        ]);
    }

    #[Route("/stand", name: "stand")]
    public function stand(SessionInterface $session): Response
    {
        $addDealer = new AddCardDealer();

        $data = $this->getData($session);

        $data->set('game_started', false);
        $data->save();

        $addDealer->addCardDealer($data);

        $game_status = $data->get('game_status');
        if (!$game_status)
        {
            $addDealer = new AddCardDealer();
            $addDealer->addCardDealer($data);

            $winner = new DecideWinner();
            $winner->decideWinner($data);

            return $this->render('project/winner.html.twig', [
                'data' => $data->getAll(),
            ]);
        }

        return $this->render('project/game.html.twig', [
            'data' => $data->getAll(),
        ]);
    }

    #[Route("/split", name: "split")]
    public function split(SessionInterface $session): Response
    {
        $data = $this->getData($session);

        $split = new Split();
        $split->addCardSplit($data);

        return $this->render('project/gameSplit.html.twig', [
            'data' => $data->getAll(),
        ]);
    }

    #[Route("/add_split", name: "add_split")]
    public function addSplit(SessionInterface $session): Response
    {
        $data = $this->getData($session);
        $add = new AddCardPlayer();

        $add->addCardSplit($data);

        if (!$data->get('game_started') || $data->get('active_hand') === false) {
            $winner = new DecideWinner();
            $winner->decideWinnerSplit($data);
    
            return $this->render('project/winner_split.html.twig', [
                'data' => $data->getAll(),
            ]);
        }

        return $this->render('project/gameSplit.html.twig', [
            'data' => $data->getAll(),
        ]);
    }

    #[Route("/stand_split", name: "stand_split")]
    public function standSplit(SessionInterface $session): Response
    {
        $data = $this->getData($session);
        $activeHand = $data->get('active_hand');

        if ($activeHand === 'hand1') {
            $data->set('active_hand', 'hand2');
        } else {
            $data->set('active_hand', false);
            $data->set('game_started', false);
        
            $addDealer = new AddCardDealer();
            $addDealer->addCardDealer($data);
        
            $winner = new DecideWinner();
            $winner->decideWinnerSplit($data);
        
            return $this->render('project/winner_split.html.twig', [
                'data' => $data->getAll(),
            ]);
        }
        
        $data->save();
        
        return $this->render('project/gameSplit.html.twig', [
            'data' => $data->getAll(),
        ]);
    }

    #[Route("/reset", name: "reset")]
    public function reset(SessionInterface $session): Response
    {
        $data = $this->getData($session);

        $data->reset();

        $data->save();

        return $this->redirectToRoute('proj_main');
    }
}