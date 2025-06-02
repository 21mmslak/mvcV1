<?php

namespace App\Tests\Project;

use App\Project\Split;
use App\Project\Data;
use PHPUnit\Framework\TestCase;

class SplitTest extends TestCase
{
    public function testSplitHand(): void
    {
        $dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'set', 'save'])
            ->getMock();

        $player = 'player1';
        $hand = 'hand1';

        $players = [
            $player => [
                'hands' => [
                    $hand => [
                        'cards' => [
                            ['value' => '8', 'suit' => 'hearts'],
                            ['value' => '8', 'suit' => 'spades']
                        ],
                        'bet' => 100
                    ]
                ]
            ]
        ];

        $deck = [
            ['value' => '5', 'suit' => 'clubs'],
            ['value' => '9', 'suit' => 'diamonds']
        ];

        $dataMock->method('get')->willReturnMap([
            ['players', [], $players],
            ['deck_of_cards', [], $deck]
        ]);

        $dataMock->expects($this->atLeastOnce())->method('set');
        $dataMock->expects($this->once())->method('save');

        $split = new Split();
        $split->splitHand($dataMock, $player, $hand);

        $this->assertTrue(true);
    }

    public function testSplitHandEarlyReturn(): void
    {
        $dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'set', 'save'])
            ->getMock();

        $dataMock->method('get')->willReturnMap([
            ['players', [], []],
            ['deck_of_cards', [], []]
        ]);

        $dataMock->expects($this->never())->method('set');
        $dataMock->expects($this->never())->method('save');

        $split = new Split();
        $split->splitHand($dataMock, 'nonexistentPlayer', 'nonexistentHand');

        $this->assertTrue(true);
    }

    public function testSplitHandEarlyReturnForInsufficientCards(): void
    {
        $dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'set', 'save'])
            ->getMock();

        $dataMock->method('get')->willReturnMap([
            ['players', [], [
                'testPlayer' => [
                    'hands' => [
                        'testHand' => [
                            'cards' => [['value' => '5', 'suit' => 'hearts']],
                            'bet' => 10,
                        ]
                    ]
                ]
            ]],
            ['deck_of_cards', [], [['value' => 'K', 'suit' => 'spades']]]
        ]);

        $dataMock->expects($this->never())->method('set');
        $dataMock->expects($this->never())->method('save');

        $split = new Split();
        $split->splitHand($dataMock, 'testPlayer', 'testHand');

        $this->assertTrue(true);
    }

    public function testGetCardReturnsDefaultForInvalidInput(): void
    {
        $split = new Split();

        $invalidInputs = [
            null,
            123,
            'invalid',
            ['wrong_key' => 'value'],
            ['value' => 'A'],
            ['suit' => 'Hearts'],
            (object)['value' => 'K', 'suit' => 'Spades']
        ];

        foreach ($invalidInputs as $input) {
            $result = $this->invokePrivate($split, 'getCard', [$input]);
            $this->assertEquals(['value' => '0', 'suit' => '', 'card' => '0'], $result);
        }
    }

    private function invokePrivate(object $object, string $methodName, array $args = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}