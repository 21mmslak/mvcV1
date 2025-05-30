<?php

namespace App\Project;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Rules
{
    protected function getIntFromSession(SessionInterface $session, string $key): int
    {
        $value = $session->get($key);

        if (is_numeric($value)) {
            return (int) $value;
        }

        return 0;
    }

    public function countPoints(array $hand): int
    {
        $points = 0;
        $aces = 0;

        foreach ($hand as $card) {
            $value = $card['value'];

            if (in_array($value, ['J', 'Q', 'K'])) {
                $points += 10;
            } elseif ($value === 'A') {
                $aces++;
                $points += 11;
            }
            $points += intval($value);
        }

        while ($points > 21 && $aces > 0) {
            $points -= 10;
            $aces--;
        }

        return $points;
    }

    public function decideWinner(Data $data): void
    {
        $game = $data->get('game_started');
        if ($game) return;

        $dealerPoints = $data->get('dealer_points');
        $coins = $data->get('coins');
        $players = $data->get('players');

        foreach ($players as $name => &$player) {
            foreach ($player['hands'] as $handName => &$hand) {
                $playerPoints = $hand['points'];
                $bet = $hand['bet'] ?? 10;

                if ($playerPoints > 21) {
                    $coins -= $bet;
                    $hand['result'] = "Dealer wins against {$handName} (bust)";
                } elseif ($dealerPoints > 21 || $playerPoints > $dealerPoints) {
                    $coins += $bet;
                    $hand['result'] = "{$handName} wins against dealer!";
                } elseif ($dealerPoints > $playerPoints) {
                    $coins -= $bet;
                    $hand['result'] = "Dealer wins against {$handName}.";
                } else {
                    $hand['result'] = "Draw with {$handName}.";
                }
            }
        }

        $data->set('coins', $coins);
        $data->set('players', $players);
        $data->save();
    }

    public function checkOver(int $points): bool
    {
        return $points >= 21;
    }
}