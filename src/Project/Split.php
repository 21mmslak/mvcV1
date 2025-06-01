<?php

namespace App\Project;

use App\Project\Rules;

class Split
{
    /**
     * Splits a player's hand into two hands.
     *
     * @param Data $data
     * @param string $player
     * @param string $hand
     * @return void
     */
    public function splitHand(Data $data, string $player, string $hand): void
    {
        $playersRaw = $data->get('players', []);
        $players = is_array($playersRaw) ? $playersRaw : [];

        $deckRaw = $data->get('deck_of_cards', []);
        $deck = is_array($deckRaw) ? $deckRaw : [];

        $rules = new Rules();

        if (!isset($players[$player]) || !is_array($players[$player])) {
            return;
        }

        $hands = $players[$player]['hands'] ?? [];
        if (!is_array($hands) || !isset($hands[$hand]) || !is_array($hands[$hand])) {
            return;
        }

        $handData = $hands[$hand];
        $currentCards = $handData['cards'] ?? [];
        if (!is_array($currentCards) || count($currentCards) < 2) {
            return;
        }

        $card1 = is_array($currentCards[0]) ? $currentCards[0] : ['value' => '0'];
        $card2 = is_array($currentCards[1]) ? $currentCards[1] : ['value' => '0'];

        $originalBet = isset($handData['bet']) && is_numeric($handData['bet']) ? (int) $handData['bet'] : 10;

        $newCard1 = array_shift($deck);
        $newCard1 = is_array($newCard1) ? $newCard1 : ['value' => '0'];

        $newCard2 = array_shift($deck);
        $newCard2 = is_array($newCard2) ? $newCard2 : ['value' => '0'];

        $hand1Cards = [$card1, $newCard1];
        $hand2Cards = [$card2, $newCard2];

        $players[$player]['hands'] = [
            'hand1' => [
                'cards' => $hand1Cards,
                'points' => $rules->countPoints($hand1Cards),
                'status' => 'active',
                'bet' => $originalBet,
            ],
            'hand2' => [
                'cards' => $hand2Cards,
                'points' => $rules->countPoints($hand2Cards),
                'status' => 'waiting',
                'bet' => $originalBet,
            ],
        ];

        $data->set('deck_of_cards', $deck);
        $data->set('players', $players);
        $data->set('active_player', $player);
        $data->set('active_hand', 'hand1');
        $data->save();
    }
}