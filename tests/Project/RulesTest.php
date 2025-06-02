<?php

namespace App\Tests\Project;

use App\Project\Rules;
use PHPUnit\Framework\TestCase;

class RulesTest extends TestCase
{
    public function testCountPointsWithMultipleAces(): void
    {
        $rules = new Rules();

        $hand = [
            ['value' => 'A', 'suit' => 'hearts'],
            ['value' => 'A', 'suit' => 'diamonds'],
            ['value' => '9', 'suit' => 'clubs'],
        ];

        $points = $rules->countPoints($hand);

        $this->assertEquals(21, $points);
    }
}