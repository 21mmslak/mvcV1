<?php

namespace App\Project;

use App\Project\StartDeck;
use App\Project\Rules;
use App\Entity\Scoreboard;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class StartBlackJack
{
    private Security $security;
    private EntityManagerInterface $em;

    public function __construct(Security $security, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->em = $em;
    }
    public function start(Data $data): void
    {
        $user = $this->security->getUser();

        if ($user && $user instanceof User) {
            $scoreboard = $this->em->getRepository(Scoreboard::class)->findOneBy(['user' => $user]);

            if (!$scoreboard) {
                $scoreboard = new Scoreboard();
                $scoreboard->setUser($user);
                $scoreboard->setCoins(5000);
                $this->em->persist($scoreboard);
                $this->em->flush();
            }

            $data->set('coins', $scoreboard->getCoins());
        } else {
            if ($data->get('coins') === null) {
                $data->set('coins', 5000);
            }
        }

        $data->set('game_started', true);

        $deck = new StartDeck();
        $cards = $deck->startGame();

        $dealerCardOne = array_splice($cards, 0, 1);
        $dealerCardTwo = array_splice($cards, 0, 1);
        $dealerCards = [];

        $players = [];
        $playerCount = count($data->get('players', ['player1' => []]));
        for ($i = 1; $i <= $playerCount; $i++) {
            $playerName = "player{$i}";

            $players[$playerName] = [
                'hands' => [
                    'hand1' => [
                        'cards' => [],
                        'points' => 0,
                        'status' => 'waiting',
                        'bet' => null
                    ]
                ]
            ];
        }

        $rules = new Rules();
        $dealerPointsStart = $rules->countPoints($dealerCardOne);
        $dealerPoints = $rules->countPoints(array_merge($dealerCardOne, $dealerCardTwo));

        $data->set('players', $players);
        $data->set('active_player', 'player1');
        $data->set('active_hand', 'hand1');
        $data->set('dealer_card_one', $dealerCardOne);
        $data->set('dealer_card_two', $dealerCardTwo);
        $data->set('dealer_points_start', $dealerPointsStart);
        $data->set('dealer_points', $dealerPoints);
        $data->set('dealer_cards', $dealerCards);
        $data->set('deck_of_cards', $cards);

        $data->save();
    }


    
    // public function start(Data $data): void
    // {
    //     if ($data->get('coins') === null) {
    //         $data->set('coins', 1000);
    //     }

    //     $data->set('game_started', true);

    //     $deck = new StartDeck();
    //     $cards = $deck->startGame();

    //     $dealerCardOne = array_splice($cards, 0, 1);
    //     $dealerCardTwo = array_splice($cards, 0, 1);
    //     $dealerCards = [];

    //     $rules = new Rules();
    //     $dealerPointsStart = $rules->countPoints($dealerCardOne);
    //     $dealerPoints = $rules->countPoints(array_merge($dealerCardOne, $dealerCardTwo));

    //     // Fake playerCards for player1 with two 5's
    //     $playerCards = [
    //         [
    //             'card' => '<span class="red-card">5♥</span>',
    //             'value' => '5',
    //             'suit' => 'Hearts'
    //         ],
    //         [
    //             'card' => '<span class="black-card">5♠</span>',
    //             'value' => '5',
    //             'suit' => 'Spades'
    //         ]
    //     ];
    //     $playerPoints = $rules->countPoints($playerCards);

    //     $players = [
    //         'player1' => [
    //             'hands' => [
    //                 'hand1' => [
    //                     'cards' => $playerCards,
    //                     'points' => $playerPoints,
    //                     'status' => 'active',
    //                     'bet' => 10
    //                 ]
    //             ]
    //         ]
    //     ];

    //     $playerCount = count($data->get('players', []));
    //     for ($i = 2; $i <= $playerCount; $i++) {
    //         $newPlayerCards = array_splice($cards, 0, 2);
    //         $newPlayerPoints = $rules->countPoints($newPlayerCards);
    //         $players["player{$i}"] = [
    //             'hands' => [
    //                 'hand1' => [
    //                     'cards' => $newPlayerCards,
    //                     'points' => $newPlayerPoints,
    //                     'status' => 'active',
    //                     'bet' => 10
    //                 ]
    //             ]
    //         ];
    //     }

    //     $data->set('players', $players);
    //     $data->set('active_player', 'player1');
    //     $data->set('active_hand', 'hand1');
    //     $data->set('dealer_card_one', $dealerCardOne);
    //     $data->set('dealer_card_two', $dealerCardTwo);
    //     $data->set('dealer_points_start', $dealerPointsStart);
    //     $data->set('dealer_points', $dealerPoints);
    //     $data->set('dealer_cards', $dealerCards);
    //     $data->set('deck_of_cards', $cards);

    //     $data->save();
    // }
}