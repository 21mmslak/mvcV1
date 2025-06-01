<?php

namespace App\Controller;

use App\Project\AddCardDealer;
use App\Project\AddCardPlayer;
use App\Project\Data;
use App\Project\DecideWinner;
use App\Project\Rules;
use App\Project\StartBlackJack;
use App\Project\Split;
use App\Entity\Scoreboard;
use App\Repository\ScoreboardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\SecurityBundle\Security;


class ProjectController extends AbstractController
{
    private Security $security;
    private EntityManagerInterface $em;
    private DecideWinner $decideWinner;

    public function __construct(Security $security, EntityManagerInterface $em, DecideWinner $decideWinner)
    {
        $this->security = $security;
        $this->em = $em;
        $this->decideWinner = $decideWinner;
    }

    private function getData(SessionInterface $session): Data
    {
        $data = new Data($session);
    
        $user = $this->security->getUser();
        if ($user && $user instanceof User) {
            $scoreboard = $this->em->getRepository(Scoreboard::class)->findOneBy(['user' => $user]);
            if ($scoreboard) {
                $data->set('coins', $scoreboard->getCoins());
            } else {
                $data->set('coins', 5000);
            }
        }
    
        return $data;
    }

    #[Route("/proj", name: "proj")]
    public function proj(): Response
    {
        return $this->render('project/index.html.twig');
    }

    #[Route("/proj/about/database", name: "proj_about_database")]
    public function projAboutData(): Response
    {
        return $this->render('project/about_data.html.twig');
    }

    #[Route("/proj_main", name: "proj_main")]
    public function projMain(SessionInterface $session): Response
    {
        $data = $this->getData($session);
        
        if (!$data->get('game_started'))
        {
            $start = new StartBlackJack($this->security, $this->em);
            $start->start($data);
        }

        return $this->render('project/gameSplit.html.twig', [
            'data' => $data->getAll(),
        ]);
    }

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
        $addCardPlayer = new AddCardPlayer($this->em, $this->security, $this->decideWinner);
    
        $players = $data->get('players');
        $players[$player]['hands'][$hand]['status'] = 'stand';
        $data->set('players', $players);
        $data->save();
    
        $gameOver = !$addCardPlayer->activateNext($data, $player, $hand);
        if ($gameOver) {
            $addDealer = new AddCardDealer();
            $addDealer->addCardDealer($data);
            $this->decideWinner->decideWinner($data);
            return $this->render('project/winner_split.html.twig', ['data' => $data->getAll()]);
        }
    
        return $this->redirectToRoute('proj_main');
    }

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
        $addCardPlayer = new AddCardPlayer($this->em, $this->security, $this->decideWinner);
    
        $bust = $addCardPlayer->addCard($data, $player, $hand);
        
        if ($bust) {
            $hasNext = $addCardPlayer->activateNext($data, $player, $hand);
            if (!$hasNext) {
                $addDealer = new AddCardDealer();
                $addDealer->addCardDealer($data);
                $this->decideWinner->decideWinner($data);
                return $this->render('project/winner_split.html.twig', ['data' => $data->getAll()]);
            }
        }

        return $this->redirectToRoute('proj_main');
    }

 
    #[Route("/dubbel_add/{player}/{hand}", name: "dubbel_add")]
    public function dubbel(SessionInterface $session, Request $request, string $player, string $hand): Response
    {
        $data = $this->getData($session);
        $addCardPlayer = new AddCardPlayer($this->em, $this->security, $this->decideWinner);

        $players = $data->get('players');
        $players[$player]['hands'][$hand]['bet'] *= 2;
        $players[$player]['hands'][$hand]['status'] = 'stand';
        $data->set('players', $players);
        $data->save();

        $bust = $addCardPlayer->addCard($data, $player, $hand);
        $gameOver = !$addCardPlayer->activateNext($data, $player, $hand);
        if ($gameOver) {
            $addDealer = new AddCardDealer();
            $addDealer->addCardDealer($data);
            $this->decideWinner->decideWinner($data);
            return $this->render('project/winner_split.html.twig', ['data' => $data->getAll()]);
        }

        return $this->redirectToRoute('proj_main');
    }


    #[Route("/set_all_bets", name: "set_all_bets", methods: ["POST"])]
    public function setAllBets(SessionInterface $session, Request $request): Response
    {
        $data = $this->getData($session);
        $players = $data->get('players', []);
        $bets = $request->request->all()['bets'] ?? [];
    
        $deck = $data->get('deck_of_cards', []);
        $rules = new Rules();
    
        foreach ($players as $playerName => &$player) {
            foreach ($player['hands'] as $handName => &$hand) {
                $hand['bet'] = (int) ($bets[$playerName][$handName] ?? 10);
                $hand['status'] = ($playerName === 'player1' && $handName === 'hand1') ? 'active' : 'waiting';

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

    #[Route("/proj/api", name: "api_proj")]
    public function api(SessionInterface $session): Response
    {
        return $this->render('project/api.html.twig');
    }

    #[Route("/proj/api/get_next_card", name: "next_card")]
    public function nextCard(SessionInterface $session): JsonResponse
    {
        $data = $this->getData($session);
    
        $deck = $data->get('deck_of_cards', []);
    
        $nextCard = !empty($deck) ? $deck[0] : null;
    
        $response = [
            'Next card' => $nextCard ?? 'No cards left or no cards'
        ];
    
        return new JsonResponse($response);
    }

    #[Route("/proj/api/scoreboard", name: "scoreboard_api")]
    public function scoreboard(ScoreboardRepository $scoreboardRepository): JsonResponse
    {
        $scoreboards = $scoreboardRepository->findBy([], ['coins' => 'DESC']);
        $scoreboardData = array_map(function ($scoreboard) {
            return [
                'username' => $scoreboard->getUser()->getUsername(),
                'coins' => $scoreboard->getCoins(),
            ];
        }, $scoreboards);
    
        $response = [
            'scoreboard' => $scoreboardData,
        ];
    
        return new JsonResponse($response);
    }

    #[Route('/proj/api/user_coins/{username}', name: 'user_coins_api', methods: ['GET'])]
    public function userCoins(string $username, ScoreboardRepository $scoreboardRepository): JsonResponse
    {
        $scoreboards = $scoreboardRepository->findAll();

        foreach ($scoreboards as $scoreboard) {
            if ($scoreboard->getUser()->getUsername() === $username) {
                return new JsonResponse([
                    'username' => $username,
                    'coins' => $scoreboard->getCoins(),
                ]);
            }
        }

        return new JsonResponse(['error' => "User '{$username}' not found or no coins."], 404);
    }

    #[Route('/proj/api/player_cards', name: 'player_cards', methods: ['GET'])]
    public function playerCads(SessionInterface $session): JsonResponse
    {
        $data = $this->getData($session);
    
        $players = $data->get('players', []);
        $playerCards = [];
    
        foreach ($players as $playerName => $player) {
            foreach ($player['hands'] as $handName => $hand) {
                $playerCards[$playerName][$handName] = [
                    'cards' => $hand['cards'] ?? [],
                    'points' => $hand['points'] ?? 0,
                    'status' => $hand['status'] ?? 'unknown'
                ];
            }
        }
    
        return new JsonResponse(['players' => $playerCards]);
    }

    #[Route('/proj/api/start_game', name: 'start_game_api', methods: ['POST'])]
    public function startGame(SessionInterface $session, Request $request): JsonResponse
    {
        $data = $this->getData($session);
        
        $startBlackJack = new StartBlackJack($this->security, $this->em);
        $startBlackJack->start($data);
        
        return new JsonResponse([
            'status' => 'success',
            'message' => 'Game started successfully',
            'data' => $data->getAll(),
        ]);
    }
}