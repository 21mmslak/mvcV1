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

        $playerCards = array_splice($cards, 0, 2);
        $dealerCards = array_splice($cards, 0, 2);

        $rules = new Rules();
        $playerPoints = $rules->countPoints($playerCards);
        $dealerPoints = $rules->countPoints($dealerCards);

        $data->set('player_cards', $playerCards);
        $data->set('dealer_cards', $dealerCards);
        $data->set('player_points', $playerPoints);
        $data->set('dealer_points', $dealerPoints);
        $data->set('deck_of_cards', $cards);
        
        $data->save();
    }
}
