<?php

namespace App\Dice;

use PHPUnit\Framework\TestCase;
use App\Dice\DiceGraphic;

/**
 * Test cases for class Dice.
 */
class DiceGraphicTest extends TestCase
{
    public function testCanInstantiateGraphicDice(): void
    {
        $dice = new DiceGraphic();
        $this->assertInstanceOf(DiceGraphic::class, $dice);
    }

    public function testGetAsStringReturnsCorrectSymbol(): void
    {
        $dice = new DiceGraphic();
        $dice->roll();

        $symbol = $dice->getAsString();

        $this->assertContains($symbol, [
            "⚀", "⚁", "⚂", "⚃", "⚄", "⚅"
        ]);
        $this->assertNotEmpty($symbol);
    }
}
