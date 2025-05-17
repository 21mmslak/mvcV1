<?php

namespace App\Dice;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for class Dice.
 */
class DiceTest extends TestCase
{
    /**
     * Construct object and verify that the object has the expected
     * properties, use no arguments.
     */
    public function testCreateDice(): void
    {
        $die = new Dice();
        $this->assertInstanceOf("\App\Dice\Dice", $die);

        $res = $die->getAsString();
        $this->assertNotEmpty($res);
    }

    public function testRollDice(): void
    {
        $die = new Dice();
        $results = [];

        for ($i = 0; $i < 100; $i++) {
            $value = $die->roll();
            $this->assertGreaterThanOrEqual(1, $value);
            $this->assertLessThanOrEqual(6, $value);
            $results[] = $value;
        }

        $this->assertGreaterThanOrEqual(1, count(array_unique($results)));
    }

    public function testGetValue(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Tärningen är inte rullad');

        $die = new Dice();
        $die->getValue();
    }

    public function testGetValueAfterRollReturnsValue(): void
    {
        $die = new Dice();
        $rolledValue = $die->roll();

        $this->assertSame($rolledValue, $die->getValue());
        $this->assertGreaterThanOrEqual(1, $rolledValue);
        $this->assertLessThanOrEqual(6, $rolledValue);
    }
}
