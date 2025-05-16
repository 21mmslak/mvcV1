<?php

namespace App\BlackJack;

use App\Card\DeckOfCards;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\BlackJack\BlackJackService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Controller\CardController;

class BlackJack
{
    /**
     * Skapar och blandar en ny kortlek.
     *
     * @return array<int, array{card: string, value: string, suit: string}>
     */
    public function startGame(): array
    {
        $deck = new DeckOfCards();
        $deck->shuffle();

        return $this->convertToArray($deck->getDeck());
    }

    /**
     * Konverterar kort till en array med enklare data.
     *
     * @param array<int, \App\Card\Card> $cards
     * @return array<int, array{card: string, value: string, suit: string}>
     */
    private function convertToArray(array $cards): array
    {
        return array_map(fn($card) => [
            'card' => $card->getAsString(),
            'value' => $card->getValue(),
            'suit' => $card->getSuit()
        ], $cards);
    }
}
