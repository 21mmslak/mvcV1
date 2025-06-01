<?php

namespace App\Project;

use App\Project\Rules;

class AddCardDealer
{
    public function addCardDealer(Data $data, int $intelligence = 80): void
    {
        $rules = new Rules();

        $dealerCardOne = $data->get('dealer_card_one', []);
        $dealerCardTwo = $data->get('dealer_card_two', []);
        $dealerCards = $data->get('dealer_cards', []);

        $dealerCardOne = is_array($dealerCardOne) ? $dealerCardOne : [$dealerCardOne];
        $dealerCardTwo = is_array($dealerCardTwo) ? $dealerCardTwo : [$dealerCardTwo];
        $dealerCards = is_array($dealerCards) ? $dealerCards : [$dealerCards];

        $allDealerCards = array_merge($dealerCardOne, $dealerCardTwo, $dealerCards);
        $dealerPoints = $rules->countPoints($allDealerCards);

        $cards = $data->get('deck_of_cards', []);
        if (!is_array($cards)) {
            $cards = (array)$cards;
        }

        while ($dealerPoints < 21 && !empty($cards)) {
            $addCard = $dealerPoints < 17 || (rand(1, 100) > $intelligence);
            if ($addCard) {
                $addedCard = array_splice($cards, 0, 1);
                $dealerCards[] = $addedCard[0];

                $allDealerCards = array_merge($dealerCardOne, $dealerCardTwo, $dealerCards);
                $dealerPoints = $rules->countPoints($allDealerCards);
            } else {
                break;
            }
        }

        $data->set('dealer_cards', $dealerCards);
        $data->set('dealer_points', $dealerPoints);
        $data->set('deck_of_cards', $cards);
        $data->save();
    }
}