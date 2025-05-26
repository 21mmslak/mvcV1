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

    public function decideWinner(int $dealer, int $player, SessionInterface $session): string
    {
        $coins = $this->getIntFromSession($session, "coins");

        if ($player > 21) {
            $coins -= 10;
            $session->set("coins", $coins);
            return "Dealer Win! Player lose :(";
        } elseif ($dealer > 21 || $player > $dealer) {
            $coins += 10;
            $session->set("coins", $coins);
            return "Player Win! Dealer lose :)";
        } elseif ($dealer > $player) {
            $coins -= 10;
            $session->set("coins", $coins);
            return "Dealer Win! Player lose :(";
        }
        return "Draw! Nobody wins.";
    }

    public function checkOver(int $points): bool
    {
        return $points >= 21;
    }
}
