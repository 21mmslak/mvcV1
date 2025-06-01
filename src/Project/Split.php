<?php

namespace App\Project;

use App\Project\Rules;

class Split
{

    public function splitHand(Data $data, string $player, string $hand)
    {
        $players = $data->get('players', []);
        $deck = $data->get('deck_of_cards');
        $rules = new Rules();
    
        $currentCards = $players[$player]['hands'][$hand]['cards'];
        $card1 = $currentCards[0];
        $card2 = $currentCards[1];
        $originalBet = $players[$player]['hands'][$hand]['bet'] ?? 10;
    
        $newCard1 = array_shift($deck);
        $newCard2 = array_shift($deck);
    

        $players[$player]['hands']['hand1'] = [
            'cards' => [$card1, $newCard1],
            'points' => $rules->countPoints([$card1, $newCard1]),
            'status' => 'active',
            'bet' => $originalBet
        ];
    
        $players[$player]['hands']['hand2'] = [
            'cards' => [$card2, $newCard2],
            'points' => $rules->countPoints([$card2, $newCard2]),
            'status' => 'waiting',
            'bet' => $originalBet
        ];
    
        $data->set('deck_of_cards', $deck);
        $data->set('players', $players);
        $data->set('active_player', $player);
        $data->set('active_hand', 'hand1');
        $data->save();
    }
}