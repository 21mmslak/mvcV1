<?php

namespace App\Project;

use App\Project\AddCardDealer;
use App\Project\Rules;

class AddCardPlayer
{
    public function addCard(Data $data): void
    {   
        $game = $data->get('game_started');
        if (!$game)
        {
            return;
        }

        $rules = new Rules();
        $cards = $data->get('deck_of_cards');
        $addedCard = array_splice($cards, 0, 1);

        $playerCards = $data->get('player_cards', []);
        $playerCards[] = $addedCard[0];

        $playerPoints = $rules->countPoints($playerCards);

        $data->set('player_cards', $playerCards);
        $data->set('player_points', $playerPoints);
        $data->set('deck_of_cards', $cards);
        $data->save();

        if ($playerPoints > 20)
        {
            $data->set('game_started', false);
            $data->save();

            $addDealer = new AddCardDealer();
            $addDealer->addCardDealer($data);
        }
    }

    public function addCardSplit(Data $data): void
    {
        $game = $data->get('game_started');
        $active = $data->get('active_hand');

        if (!$game) {
            return;
        }

        $rules = new Rules();
        $cards = $data->get('deck_of_cards');

        if (!empty($cards)) {
            $addedCard = array_splice($cards, 0, 1);

            if ($active === 'hand1') {
                $hand1 = $data->get('hand1', []);
                $hand1[] = $addedCard[0];
                $hand1Points = $rules->countPoints($hand1);
                $data->set('hand1', $hand1);
                $data->set('hand1Points', $hand1Points);

                if ($hand1Points > 20) {
                    $data->set('active_hand', 'hand2');
                }
            } elseif ($active === 'hand2') {
                $hand2 = $data->get('hand2', []);
                $hand2[] = $addedCard[0];
                $hand2Points = $rules->countPoints($hand2);
                $data->set('hand2', $hand2);
                $data->set('hand2Points', $hand2Points);

                if ($hand2Points > 20) {
                    $data->set('game_started', false);
                    $data->set('active_hand', false);

                    $addDealer = new AddCardDealer();
                    $addDealer->addCardDealer($data);
                }
            }

            $data->set('deck_of_cards', $cards);
        }

        $data->save();
    }
}
