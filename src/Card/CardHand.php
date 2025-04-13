<?php

namespace App\Card;

use App\Card\Card;

class CardHand
{
    private array $hand = [];

    public function add(Card $card): void
    {
        $this->hand[] = $card;
    }

    public function count(): int
    {
        return count($this->hand);
    }

    public function getCards(): array
    {
        return $this->hand;
    }

    public function getValues(): array
    {
        $values = [];
        foreach ($this->hand as $card) {
            $values[] = $card->getValue();
        }
        return $values;
    }
}
