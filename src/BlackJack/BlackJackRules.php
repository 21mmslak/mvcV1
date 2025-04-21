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

    public function decideWinner(int $dealer, int $player): string
    {
        if ($player > 21) {
            return "Dealer Win! Player lose :(";
        } else if ($dealer > 21) {
            return "Player Win! Dealer lose :)";
        } else if ($player < $dealer) {
            return "Dealer Win! Player lose :(";
        } else if ($dealer < $player) {
            return "Player Win! Dealer lose :)";
        } else {
            return "Draw! Nobody wins.";
        }
    }

    public function checkOver(int $points): bool
    {
        return $points >= 21;
    }
}
