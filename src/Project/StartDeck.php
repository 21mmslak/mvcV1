<?php

namespace App\Project;

use App\Card\DeckOfCards;

class StartDeck
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
        return array_map(fn ($card) => [
            'card' => $card->getAsString(),
            'value' => $card->getValue(),
            'suit' => $card->getSuit()
        ], $cards);
    }
}
