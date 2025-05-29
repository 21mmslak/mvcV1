<?php

namespace App\Project;

use App\Project\Rules;

class AddCardDealer
{
    public function addCardDealer(Data $data): void
    {   
        $game = $data->get('game_started');
        if ($game)
        {
            return;
        }

        $rules = new Rules();

        $dealerCardOne = $data->get('dealer_card_one', []);
        $dealerCardTwo = $data->get('dealer_card_two', []);
        $dealerCards = $data->get('dealer_cards', []);
        $allDealerCards = array_merge($dealerCardOne, $dealerCardTwo, $dealerCards);
        $dealerPoints = $rules->countPoints($allDealerCards);

        $cards = $data->get('deck_of_cards');

        while ($dealerPoints < 18 && !empty($cards))
        {
            $addedCard = array_splice($cards, 0, 1);
            $dealerCards[] = $addedCard[0];

            $allDealerCards = array_merge($dealerCardOne, $dealerCardTwo, $dealerCards);
            $dealerPoints = $rules->countPoints($allDealerCards);
        }

        $data->set('dealer_cards', $dealerCards);
        $data->set('dealer_points', $dealerPoints);
        $data->set('deck_of_cards', $cards);
        $data->save();
    }
}
