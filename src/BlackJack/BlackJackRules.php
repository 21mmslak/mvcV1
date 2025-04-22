<?php

namespace App\BlackJack;

use App\Card\DeckOfCards;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Controller\CardController;

class BlackJackRules
{
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
            } else {
                $points += intval($value);
            }
        }

        while ($points > 21 && $aces > 0) {
            $points -= 10;
            $aces--;
        }

        return $points;
    }

    public function decideWinner(int $dealer, int $player, SessionInterface $session): string
    {
        $coins = $session->get("coins", 100);

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
        } else {
            return "Draw! Nobody wins.";
        }
    }

    public function checkOver(int $points): bool
    {
        return $points >= 21;
    }
}
