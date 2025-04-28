<?php

namespace App\Card;

use App\Card\Card;

class CardHand
{
    /**
     * Summary of hand
     * @var Card[]
     */
    private array $hand = [];

    public function add(Card $card): void
    {
        $this->hand[] = $card;
    }

    public function count(): int
    {
        return count($this->hand);
    }

    /**
     * Summary of getCards
     * @return Card[]
     */
    public function getCards(): array
    {
        return $this->hand;
    }

    /**
     * Summary of getValues
     * @return string[]
     */
    public function getValues(): array
    {
        $values = [];
        foreach ($this->hand as $card) {
            $values[] = $card->getValue();
        }
        return $values;
    }
}
