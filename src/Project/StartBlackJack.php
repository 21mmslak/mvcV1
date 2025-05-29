<?php

namespace App\Project;

use App\Project\StartDeck;
use App\Project\Rules;

class StartBlackJack
{
    public function start(Data $data): void
    {
        if ($data->get('coins') === null) {
            $data->set('coins', 1100);
        }

        $data->set('game_started', true);

        $deck = new StartDeck();
        $cards = $deck->startGame();

        // $playerCards = array_splice($cards, 0, 2);
        $dealerCardOne = array_splice($cards, 0, 1);
        $dealerCardTwo = array_splice($cards, 0, 1);
        $dealerCards = [];
        
        //fake data
        $playerCards = [
            [
                'card' => '<span class=\"black-card\">K♠</span>',
                'value' => 'K',
                'suit' => 'Spades'
            ],
            [
                'card' => '<span class=\"red-card\">K♥</span>',
                'value' => 'K',
                'suit' => 'Hearts'
            ]
        ];
        
        
        $rules = new Rules();
        $playerPoints = $rules->countPoints($playerCards);
        $dealerPointsStart = $rules->countPoints($dealerCardOne);
        $dealerPoints = $rules->countPoints($dealerCardOne + $dealerCardTwo);

        $data->set('player_cards', $playerCards);
        $data->set('dealer_card_one', $dealerCardOne);
        $data->set('dealer_card_two', $dealerCardTwo);
        $data->set('player_points', $playerPoints);
        $data->set('dealer_points_start', $dealerPointsStart);
        $data->set('dealer_points', $dealerPoints);
        $data->set('dealer_cards', $dealerCards);
        $data->set('deck_of_cards', $cards);

        $data->save();
    }
}
