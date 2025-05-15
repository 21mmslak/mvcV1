<?php

namespace App\Dice;

use PHPUnit\Framework\TestCase;
use App\Dice\DiceHand;
use App\Dice\Dice;


class DiceHandTest extends TestCase
{
    public function testAdd() : void
    {
        $testHand = new DiceHand();
        $dice = new Dice();

        $testHand->add($dice);

        $reflection = new \ReflectionClass($testHand);
        $property = $reflection->getProperty('hand');
        $property->setAccessible(true);

        /** @var Dice[] $hand */
        $hand = $property->getValue($testHand);

        $this->assertCount(1, $hand);
        $this->assertInstanceOf(Dice::class, $hand[0]);
    }

    public function testRollDice(): void
    {
        $hand = new DiceHand();
        $hand->add(new Dice());
        $hand->add(new Dice());

        $hand->roll();

        foreach ($hand->getValues() as $value) {
            $this->assertGreaterThanOrEqual(1, $value);
            $this->assertLessThanOrEqual(6, $value);
        }
    }

    public function testGetNumberDice(): void
    {
        $hand = new DiceHand();
        $hand->add(new Dice());
        $hand->add(new Dice());

        $count = $hand->getNumberDices();

        $this->assertEquals(2, $count);
    }

    public function testGetString(): void
    {
        $hand = new DiceHand();
        $hand->add(new DiceGraphic());
        $hand->add(new DiceGraphic());

        $hand->roll();
        $stringValues = $hand->getString();

        $this->assertCount(2, $stringValues);

        foreach ($stringValues as $symbol) {
            $this->assertIsString($symbol);
            $this->assertNotEmpty($symbol);
        }
    }
}
