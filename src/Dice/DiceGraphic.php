<?php

namespace App\Dice;

class DiceGraphic extends Dice
{
    /**
     * Grafic representation of a dice
     * @var string[]
     */
    private $representation = [
        '⚀',
        '⚁',
        '⚂',
        '⚃',
        '⚄',
        '⚅',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getAsString(): string
    {
        return $this->representation[$this->value - 1];
    }
}
