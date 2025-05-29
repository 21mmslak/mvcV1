<?php

namespace App\Project;

use App\Project\StartDeck;
use App\Project\Rules;

class StartBlackJack
{
    // public function start(Data $data): void
    // {
    //     if ($data->get('coins') === null) {
    //         $data->set('coins', 1000);
    //     }

    //     $data->set('game_started', true);

    //     $deck = new StartDeck();
    //     $cards = $deck->startGame();

    //     $playerCards = array_splice($cards, 0, 2);
    //     $dealerCardOne = array_splice($cards, 0, 1);
    //     $dealerCardTwo = array_splice($cards, 0, 1);
    //     $dealerCards = [];
        

    //     $rules = new Rules();
    //     $playerPoints = $rules->countPoints($playerCards);
    //     $dealerPointsStart = $rules->countPoints($dealerCardOne);
    //     $dealerPoints = $rules->countPoints($dealerCardOne + $dealerCardTwo);

    //     $players = [];
    //     $playerCount = count($data->get('players', ['player1' => []]));
    //     for ($i = 1; $i <= $playerCount; $i++) {
    //         $playerName = "player{$i}";
    //         $playerCards = array_splice($cards, 0, 2);
    //         $playerPoints = $rules->countPoints($playerCards);
    //         $players[$playerName] = [
    //             'hands' => [
    //                 'hand1' => [
    //                     'cards' => $playerCards,
    //                     'points' => $playerPoints,
    //                     'status' => 'active'
    //                 ]
    //             ]
    //         ];
    //     }

    //     $data->set('players', $players);
    //     $data->set('active_player', 'player1');
    //     $data->set('active_hand', 'hand1');

    //     //fake data
    //     // $playerCards = [
    //     //     [
    //     //         'card' => '<span class=\"black-card\">K♠</span>',
    //     //         'value' => 'K',
    //     //         'suit' => 'Spades'
    //     //     ],
    //     //     [
    //     //         'card' => '<span class=\"red-card\">K♥</span>',
    //     //         'value' => 'K',
    //     //         'suit' => 'Hearts'
    //     //     ]
    //     // ];
        
        
        

    //     // $data->set('player_cards', $playerCards);
    //     $data->set('dealer_card_one', $dealerCardOne);
    //     $data->set('dealer_card_two', $dealerCardTwo);
    //     // $data->set('player_points', $playerPoints);
    //     $data->set('dealer_points_start', $dealerPointsStart);
    //     $data->set('dealer_points', $dealerPoints);
    //     $data->set('dealer_cards', $dealerCards);
    //     $data->set('playing_hands', 1);
    //     $data->set('deck_of_cards', $cards);

    //     $data->save();
    // }


    
    public function start(Data $data): void
    {
        if ($data->get('coins') === null) {
            $data->set('coins', 1000);
        }

        $data->set('game_started', true);

        $deck = new StartDeck();
        $cards = $deck->startGame();

        $dealerCardOne = array_splice($cards, 0, 1);
        $dealerCardTwo = array_splice($cards, 0, 1);
        $dealerCards = [];

        $rules = new Rules();
        $dealerPointsStart = $rules->countPoints($dealerCardOne);
        $dealerPoints = $rules->countPoints(array_merge($dealerCardOne, $dealerCardTwo));

        // Fake playerCards for player1 with two 5's
        $playerCards = [
            [
                'card' => '<span class="red-card">5♥</span>',
                'value' => '5',
                'suit' => 'Hearts'
            ],
            [
                'card' => '<span class="black-card">5♠</span>',
                'value' => '5',
                'suit' => 'Spades'
            ]
        ];
        $playerPoints = $rules->countPoints($playerCards);

        $players = [
            'player1' => [
                'hands' => [
                    'hand1' => [
                        'cards' => $playerCards,
                        'points' => $playerPoints,
                        'status' => 'active'
                    ]
                ]
            ]
        ];

        // Lägg till fler spelare om de finns
        $playerCount = count($data->get('players', []));
        for ($i = 2; $i <= $playerCount; $i++) {
            $newPlayerCards = array_splice($cards, 0, 2);
            $newPlayerPoints = $rules->countPoints($newPlayerCards);
            $players["player{$i}"] = [
                'hands' => [
                    'hand1' => [
                        'cards' => $newPlayerCards,
                        'points' => $newPlayerPoints,
                        'status' => 'active'
                    ]
                ]
            ];
        }

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
}
