<?php

namespace App\Project;

class DecideWinner
{
    public function decideWinner(Data $data): void
    {
        $game = $data->get('game_started');
        if ($game) {
            return;
        }

        $dealerPoints = $data->get('dealer_points');
        $playerPoints = $data->get('player_points');
        $coins = $data->get('coins');

        if ($playerPoints > 21) {
            $coins -= 10;
            $data->set('result', 'Dealer Win! Player busts.');
        } elseif ($dealerPoints > 21) {
            $coins += 10;
            $data->set('result', 'Player wins! Dealer busts.');
        } elseif ($playerPoints > $dealerPoints) {
            $coins += 10;
            $data->set('result', 'Player wins with higher points!');
        } elseif ($dealerPoints > $playerPoints) {
            $coins -= 10;
            $data->set('result', 'Dealer wins with higher points!');
        } else {
            $data->set('result', 'Draw! Nobody wins.');
        }

        $data->set('coins', $coins);
        $data->save();
    }

    public function decideWinnerSplit(Data $data): void
    {
        $game = $data->get('game_started');
        if ($game) {
            return;
        }

        $dealerPoints = $data->get('dealer_points');
        $coins = $data->get('coins');
        $results = [];

        foreach (['hand1', 'hand2'] as $hand) {
            $playerPoints = $data->get("{$hand}Points", 0);

            if ($playerPoints > 21) {
                $coins -= 10;
                $results[] = "Dealer wins against {$hand} (Player busts)";
            } elseif ($dealerPoints > 21) {
                $coins += 10;
                $results[] = "Player wins with {$hand} (Dealer busts)";
            } elseif ($playerPoints > $dealerPoints) {
                $coins += 10;
                $results[] = "Player wins with {$hand}!";
            } elseif ($dealerPoints > $playerPoints) {
                $coins -= 10;
                $results[] = "Dealer wins against {$hand}!";
            } else {
                $results[] = "Draw with {$hand}!";
            }
        }

        $data->set('coins', $coins);
        $data->set('results', $results);
        $data->save();
    }
}