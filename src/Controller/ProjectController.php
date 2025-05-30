<?php

namespace App\Controller;

use App\Project\AddCardDealer;
use App\Project\AddCardPlayer;
use App\Project\Data;
use App\Project\DecideWinner;
use App\Project\Rules;
use App\Project\StartBlackJack;
use App\Project\Split;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
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

        return $this->render('project/gameSplit.html.twig', [
            'data' => $data->getAll(),
        ]);
    }

    // #[Route("/add_card", name: "add_card")]
    // public function addCardControll(SessionInterface $session): Response
    // {
    //     $data = $this->getData($session);

    //     $add = new AddCardPlayer();
    //     $add->addCard($data);

    //     $activePlayer = $data->get('active_player');
    //     $gameStarted = $data->get('game_started');

    //     if (!$gameStarted || !$activePlayer)
    //     {
    //         $addDealer = new AddCardDealer();
    //         $addDealer->addCardDealer($data);

    //         $winner = new DecideWinner();
    //         $winner->decideWinner($data);

    //         return $this->render('project/winner_split.html.twig', [
    //             'data' => $data->getAll(),
    //         ]);
    //     }
        
    //     return $this->render('project/gameSplit.html.twig', [
    //         'data' => $data->getAll(),
    //     ]);
    // }

    #[Route("/split/{player}/{hand}", name: "split")]
    public function split(SessionInterface $session, string $player, string $hand): Response
    {
        $data = $this->getData($session);
        $split = new Split();
        $split->splitHand($data, $player, $hand);

        return $this->redirectToRoute('proj_main');
    }


    #[Route("/stand/{player}/{hand}", name: "stand")]
    public function stand(SessionInterface $session, string $player, string $hand): Response
    {
        $data = $this->getData($session);
        $players = $data->get('players', []);
    
        $players[$player]['hands'][$hand]['status'] = 'stand';

        $foundNext = false;
        $playerNames = array_keys($players);
        $currentPlayerIndex = array_search($player, $playerNames);
    
        for ($i = $currentPlayerIndex; $i < count($playerNames); $i++) {
            $currentPlayer = $playerNames[$i];
            $hands = array_keys($players[$currentPlayer]['hands']);
    
            $startIndex = ($i == $currentPlayerIndex) ? array_search($hand, $hands) + 1 : 0;
    
            for ($j = $startIndex; $j < count($hands); $j++) {
                if (($players[$currentPlayer]['hands'][$hands[$j]]['status'] ?? '') == 'active') {
                    $data->set('active_player', $currentPlayer);
                    $data->set('active_hand', $hands[$j]);
                    $foundNext = true;
                    break 2;
                }
            }
        }
    
        if (!$foundNext) {
            $data->set('active_player', null);
            $data->set('active_hand', null);
            $data->set('game_started', false);
    
            $dealer = new AddCardDealer();
            $dealer->addCardDealer($data);
    
            $winner = new DecideWinner();
            $winner->decideWinner($data);
    
            $data->save();
    
            return $this->render('project/winner_split.html.twig', [
                'data' => $data->getAll(),
            ]);
        }
    
        $data->set('players', $players);
        $data->save();
    
        return $this->redirectToRoute('proj_main');
    }



    // #[Route("/stand", name: "stand")]
    // public function stand(SessionInterface $session): Response
    // {
    //     $data = $this->getData($session);
    //     $players = $data->get('players');
    //     $current = $data->get('active_player');

    //     $keys = array_keys($players);
    //     $index = array_search($current, $keys);
    
    //     if ($index !== false && $index + 1 < count($keys)) {
    //         $next = $keys[$index + 1];
    //         $data->set('active_player', $next);
    //     } else {
    //         $data->set('active_player', null);
    //         $data->set('game_started', false);
    
    //         $addDealer = new AddCardDealer();
    //         $addDealer->addCardDealer($data);
    
    //         $winner = new DecideWinner();
    //         $winner->decideWinner($data);
    
    //         $data->save();
    
    //         return $this->render('project/winner_split.html.twig', [
    //             'data' => $data->getAll(),
    //         ]);
    //     }
    
    //     $data->save();
    //     return $this->render('project/gameSplit.html.twig', [
    //         'data' => $data->getAll(),
    //     ]);
    // }

    // #[Route("/split", name: "split")]
    // public function split(SessionInterface $session): Response
    // {
    //     $data = $this->getData($session);

    //     $split = new Split();
    //     $split->addCardSplit($data);

    //     return $this->render('project/gameSplit.html.twig', [
    //         'data' => $data->getAll(),
    //     ]);
    // }

    // #[Route("/add_split", name: "add_split")]
    // public function addSplit(SessionInterface $session): Response
    // {
    //     $data = $this->getData($session);
    //     $add = new AddCardPlayer();

    //     $add->addCardSplit($data);

    //     if (!$data->get('game_started') || $data->get('active_hand') === false) {
    //         $winner = new DecideWinner();
    //         $winner->decideWinnerSplit($data);
    
    //         return $this->render('project/winner_split.html.twig', [
    //             'data' => $data->getAll(),
    //         ]);
    //     }

    //     return $this->render('project/gameSplit.html.twig', [
    //         'data' => $data->getAll(),
    //     ]);
    // }

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

    // #[Route("/add_hand", name: "add_hand")]
    // public function addHand(SessionInterface $session): Response
    // {
    //     $data = $this->getData($session);
    
    //     $players = $data->get('players', []);
    //     $cards = $data->get('deck_of_cards');
    //     $rules = new Rules();
    
    //     if (count($players) < 3) {
    //         $newPlayer = 'player' . (count($players) + 1);
    //         $playerCards = array_splice($cards, 0, 2);
    //         $playerPoints = $rules->countPoints($playerCards);
            
    //         $players[$newPlayer] = [
    //             'cards' => $playerCards,
    //             'points' => $playerPoints,
    //             'status' => 'active'
    //         ];
    
    //         $data->set('deck_of_cards', $cards);
    //         $data->set('players', $players);
    //         $data->save();
    //     }
    
    //     return $this->redirectToRoute('proj_main');
    // }



    #[Route("/add_hand", name: "add_hand")]
    public function addHand(SessionInterface $session): Response
    {
        $data = $this->getData($session);
        $players = $data->get('players', []);
        $cards = $data->get('deck_of_cards');
        $rules = new Rules();
    
        if (count($players) < 3) {
            $newPlayer = 'player' . (count($players) + 1);
            $playerCards = array_splice($cards, 0, 2);
            $playerPoints = $rules->countPoints($playerCards);
    
            $players[$newPlayer] = [
                'hands' => [
                    'hand1' => [
                        'cards' => $playerCards,
                        'points' => $playerPoints,
                        'status' => 'active'
                    ]
                ]
            ];
    
            $data->set('deck_of_cards', $cards);
            $data->set('players', $players);
            $data->save();
        }
    
        return $this->redirectToRoute('proj_main');
    }



    #[Route("/remove_hand/{name}", name: "remove_hand")]
    public function removeHand(SessionInterface $session, string $name): Response
    {
        $data = $this->getData($session);
        $players = $data->get('players', []);
    
        if (count($players) > 1) {
            $keys = array_keys($players);
            $lastPlayer = end($keys);
    
            if ($name === $lastPlayer) {
                unset($players[$name]);

                if ($data->get('active_player') == $name) {
                    $nextPlayer = reset($players);
                    $nextName = key($players);
                    $data->set('active_player', $nextName ?: null);
                }
    
                $data->set('players', $players);
                $data->save();
            }
        }
    
        return $this->redirectToRoute('proj_main');
    }


    #[Route("/add_card/{player}/{hand}", name: "add_card")]
    public function addCard(SessionInterface $session, string $player, string $hand): Response
    {
        $data = $this->getData($session);
        $addCardPlayer = new AddCardPlayer();
    
        $gameOver = $addCardPlayer->addCard($data, $player, $hand);
    
        if ($gameOver) {
            return $this->render('project/winner_split.html.twig', [
                'data' => $data->getAll(),
            ]);
        }
    
        return $this->redirectToRoute('proj_main');
    }


    #[Route("/set_bet/{player}/{hand}", name: "set_bet", methods: ["POST"])]
    public function setBet(SessionInterface $session, Request $request, string $player, string $hand): Response
    {
        $data = $this->getData($session);
        $players = $data->get('players', []);
        $bet = (int) $request->request->get('bet');
    
        $players[$player]['hands'][$hand]['bet'] = $bet;
    
        $data->set('players', $players);
        $data->save();
    
        return $this->redirectToRoute('proj_main');
    }


    #[Route("/set_all_bets", name: "set_all_bets", methods: ["POST"])]
    public function setAllBets(SessionInterface $session, Request $request): Response
    {
        $data = $this->getData($session);
        $players = $data->get('players', []);

        $bets = $request->request->all()['bets'] ?? [];

        foreach ($bets as $playerName => $playerBets) {
            foreach ($playerBets as $handName => $bet) {
                $players[$playerName]['hands'][$handName]['bet'] = (int) $bet;
                $players[$playerName]['hands'][$handName]['status'] = 'active';
            }
        }

        $deck = $data->get('deck_of_cards', []);
        $rules = new Rules();

        foreach ($players as $playerName => &$player) {
            foreach ($player['hands'] as &$hand) {
                if (empty($hand['cards'])) {
                    $hand['cards'] = array_splice($deck, 0, 2);
                    $hand['points'] = $rules->countPoints($hand['cards']);
                }
            }
        }

        $data->set('deck_of_cards', $deck);
        $data->set('players', $players);
        $data->save();

        return $this->redirectToRoute('proj_main');
    }

    #[Route("/reset", name: "reset")]
    public function reset(SessionInterface $session): Response
    {
        $data = $this->getData($session);

        $data->set('game_started', false);
        $data->set('player_cards', []);
        $data->set('dealer_card_one', []);
        $data->set('dealer_card_two', []);
        $data->set('player_points', 0);
        $data->set('dealer_points_start', 0);
        $data->set('dealer_points', 0);
        $data->set('dealer_cards', []);
        $data->set('result', '');
        
        $data->save();

        return $this->redirectToRoute('proj_main');
    }
}