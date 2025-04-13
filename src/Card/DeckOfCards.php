<?php

namespace App\Card;

use App\Card\Card;
use App\Card\CardGrafic;

class DeckOfCards
{
    private array $deck = [];

    public function __construct()
    {
        $this->generateDeck();
    }

    private function generateDeck(): void
    {
        $suits = ['Hearts', 'Diamonds', 'Clubs', 'Spades'];
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];

        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $this->deck[] = new CardGrafic($suit, $value);
            }
        }
    }

    public function shuffle(): void
    {
        shuffle($this->deck);
    }

    public function draw(): ?Card
    {
        return array_pop($this->deck);
    }

    public function remaining(): int
    {
        return count($this->deck);
    }

    public function getDeck(): array
    {
        return $this->deck;
    }

    public function reset(): void
    {
        $this->deck = [];
        $this->generateDeck();
    }
}
