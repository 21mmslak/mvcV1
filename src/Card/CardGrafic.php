<?php

namespace App\Card;

use App\Card\Card;

class CardGrafic extends Card
{
    private array $symbols = [
        'Hearts' => '♥',
        'Diamonds' => '♦',
        'Clubs' => '♣',
        'Spades' => '♠',
    ];

    public function __construct(string $suit, string $value)
    {
        parent::__construct($suit, $value);
    }

    public function getAsString(): string
    {
        $symbol = $this->symbols[$this->suit] ?? '?';
        return "{$this->value}{$symbol}";
    }
}
