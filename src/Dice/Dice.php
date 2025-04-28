<?php

namespace App\Dice;

use LogicException;

class Dice
{
    /**
     * Value of the dice
     * @var int|null
     */
    protected ?int $value = null;

    public function __construct()
    {
        // $this->value = null;
    }

    public function roll(): int
    {
        $this->value = random_int(1, 6);
        return $this->value;
    }

    public function getValue(): int
    {
        if ($this->value === null) {
            throw new LogicException('Tärningen är inte rullad');
        }
        return $this->value;
    }

    public function getAsString(): string
    {
        return "[{$this->value}]";
    }
}
