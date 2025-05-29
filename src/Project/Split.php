<?php

namespace App\Project;

use App\Project\Rules;

class Split
{

    public function splitHand(Data $data, string $player, string $hand): void
    {
        $players = $data->get('players', []);
        $cards = $data->get('deck_of_cards');
        $rules = new Rules();

        if (!isset($players[$player]['hands'][$hand]['cards']) || count($players[$player]['hands'][$hand]['cards']) < 2) {
            return;
        }

        $originalCards = $players[$player]['hands'][$hand]['cards'];

        if ($originalCards[0]['value'] !== $originalCards[1]['value']) {
            return;
        }

        unset($players[$player]['hands'][$hand]);

        $newHand1 = $hand . '_split1';
        $newHand2 = $hand . '_split2';

        $newCard1 = array_shift($cards);
        $newCard2 = array_shift($cards);

        $players[$player]['hands'][$newHand1] = [
            'cards' => [$originalCards[0], $newCard1],
            'points' => $rules->countPoints([$originalCards[0], $newCard1]),
            'status' => 'active'
        ];

        $players[$player]['hands'][$newHand2] = [
            'cards' => [$originalCards[1], $newCard2],
            'points' => $rules->countPoints([$originalCards[1], $newCard2]),
            'status' => 'active'
        ];

        $data->set('active_player', $player);
        $data->set('active_hand', $newHand1);
        
        $data->set('deck_of_cards', $cards);
        $data->set('players', $players);
        $data->save();
    }




    // public function addCardSplit(Data $data): void
    // {   
    //     $game = $data->get('game_started');
    //     if (!$game)
    //     {
    //         return;
    //     }

    //     $rules = new Rules();
    //     $cards = $data->get('deck_of_cards');

    //     $playerCards = $data->get('player_cards');

    //     $hand1 = [$playerCards[0]];
    //     $addCardHand1 = array_splice($cards, 0, 1);
    //     $hand1[] = $addCardHand1[0];
    //     $hand1Points = $rules->countPoints($hand1);

    //     $hand2 = [$playerCards[1]];
    //     $addCardHand2 = array_splice($cards, 0, 1);
    //     $hand2[] = $addCardHand2[0];
    //     $hand2Points = $rules->countPoints($hand2);

    //     $data->set('hand1', $hand1);
    //     $data->set('active_hand', 'hand1');
    //     $data->set('hand2', $hand2);
    //     $data->set('hand1Points', $hand1Points);
    //     $data->set('hand2Points', $hand2Points);

    //     $data->set('deck_of_cards', $cards);
    //     $data->save();
    // }
}
