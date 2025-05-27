<?php

namespace App\Project;

use App\Project\Rules;

class Split
{
    public function addCardSplit(Data $data): void
    {   
        $game = $data->get('game_started');
        if (!$game)
        {
            return;
        }

        $rules = new Rules();
        $cards = $data->get('deck_of_cards');

        $playerCards = $data->get('player_cards');

        $hand1 = [$playerCards[0]];
        $addCardHand1 = array_splice($cards, 0, 1);
        $hand1[] = $addCardHand1[0];
        $hand1Points = $rules->countPoints($hand1);

        $hand2 = [$playerCards[1]];
        $addCardHand2 = array_splice($cards, 0, 1);
        $hand2[] = $addCardHand2[0];
        $hand2Points = $rules->countPoints($hand2);

        $data->set('hand1', $hand1);
        $data->set('active_hand', 'hand1');
        $data->set('hand2', $hand2);
        $data->set('hand1Points', $hand1Points);
        $data->set('hand2Points', $hand2Points);

        $data->set('deck_of_cards', $cards);
        $data->save();
    }
}
