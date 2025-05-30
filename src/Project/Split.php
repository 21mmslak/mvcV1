<?php

namespace App\Project;

use App\Project\Rules;

class Split
{

    // public function splitHand(Data $data, string $player, string $hand): void
    // {
    //     $players = $data->get('players', []);
    //     $cards = $data->get('deck_of_cards');
    //     $rules = new Rules();

    //     if (!isset($players[$player]['hands'][$hand]['cards']) || count($players[$player]['hands'][$hand]['cards']) < 2) {
    //         return;
    //     }

    //     $originalCards = $players[$player]['hands'][$hand]['cards'];

    //     if ($originalCards[0]['value'] !== $originalCards[1]['value']) {
    //         return;
    //     }

    //     unset($players[$player]['hands'][$hand]);

    //     $newHand1 = $hand . '_split1';
    //     $newHand2 = $hand . '_split2';

    //     $newCard1 = array_shift($cards);
    //     $newCard2 = array_shift($cards);

    //     $players[$player]['hands'][$newHand1] = [
    //         'cards' => [$originalCards[0], $newCard1],
    //         'points' => $rules->countPoints([$originalCards[0], $newCard1]),
    //         'status' => 'active'
    //     ];

    //     $players[$player]['hands'][$newHand2] = [
    //         'cards' => [$originalCards[1], $newCard2],
    //         'points' => $rules->countPoints([$originalCards[1], $newCard2]),
    //         'status' => 'active'
    //     ];

    //     $data->set('active_player', $player);
    //     $data->set('active_hand', $newHand1);
        
    //     $data->set('deck_of_cards', $cards);
    //     $data->set('players', $players);
    //     $data->save();
    // }


    public function splitHand(Data $data, string $player, string $hand): void
    {
        $players = $data->get('players', []);
        $cards = $data->get('deck_of_cards');
        $coins = $data->get('coins');
        $rules = new Rules();

        $currentHand = $players[$player]['hands'][$hand];

        if (count($currentHand['cards']) != 2 || $currentHand['cards'][0]['value'] != $currentHand['cards'][1]['value']) {
            return;
        }

        $bet = $currentHand['bet'] ?? 0;
        if ($coins < $bet) {
            return;
        }

        $originalCards = $currentHand['cards'];
        $newCard1 = array_splice($cards, 0, 1)[0];
        $newCard2 = array_splice($cards, 0, 1)[0];

        $players[$player]['hands']['hand1'] = [
            'cards' => [$originalCards[0], $newCard1],
            'points' => $rules->countPoints([$originalCards[0], $newCard1]),
            'status' => 'active',
            'bet' => $bet
        ];

        $players[$player]['hands']['hand2'] = [
            'cards' => [$originalCards[1], $newCard2],
            'points' => $rules->countPoints([$originalCards[1], $newCard2]),
            'status' => 'active',
            'bet' => $bet
        ];

        $coins -= $bet;

        $data->set('players', $players);
        $data->set('deck_of_cards', $cards);
        $data->set('coins', $coins);
        $data->set('active_hand', 'hand1');
        $data->save();
    }
}