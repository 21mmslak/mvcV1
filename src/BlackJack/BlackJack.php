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

class BlackJack
{
    /**
     * Startar spelet och lägger blandad kortlek i sessionen.
     *
     * @param SessionInterface $session
     * @return array<int, array{card: string, value: string, suit: string}>
     */
    public function startGame(
        SessionInterface $session
    ): array {
        $deck = new DeckOfCards();
        $deck->shuffle();

        $cards = $deck->getDeck();

        $cardData = array_map(function ($card) {
            return [
                "card" => $card->getAsString(),
                "value" => $card->getValue(),
                "suit" => $card->getSuit()
            ];
        }, $cards);

        $session->set("shuffled_deck", $cardData);

        return $cardData;
    }

}
