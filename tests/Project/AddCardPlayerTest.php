<?php

namespace App\Tests\Project;

use App\Project\AddCardPlayer;
use App\Project\Data;
use App\Project\DecideWinner;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class AddCardPlayerTest extends TestCase
{
    private $emMock;
    private $securityMock;
    private $decideWinnerMock;
    private $dataMock;

    protected function setUp(): void
    {
        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->securityMock = $this->createMock(Security::class);
        $this->decideWinnerMock = $this->createMock(DecideWinner::class);
        $this->dataMock = $this->createMock(Data::class);
    }

    public function testAddCardWithValidCard(): void
    {
        $addCardPlayer = new AddCardPlayer($this->emMock, $this->securityMock, $this->decideWinnerMock);

        $players = [
            'player1' => [
                'hands' => [
                    'hand1' => [
                        'cards' => [
                            ['value' => '5', 'suit' => 'hearts']
                        ]
                    ]
                ]
            ]
        ];

        $deck = [
            ['value' => '7', 'suit' => 'spades']
        ];

        $this->dataMock->method('get')
            ->willReturnMap([
                ['players', null, $players],
                ['deck_of_cards', null, $deck],
            ]);

        $this->dataMock->expects($this->atLeastOnce())->method('set');
        $this->dataMock->expects($this->once())->method('save');

        $result = $addCardPlayer->addCard($this->dataMock, 'player1', 'hand1', false);
        $this->assertFalse($result);
    }

    public function testAddCardReturnsFalseIfCardNotArray(): void
    {
        $addCardPlayer = new AddCardPlayer($this->emMock, $this->securityMock, $this->decideWinnerMock);

        $players = [
            'player1' => [
                'hands' => [
                    'hand1' => [
                        'cards' => []
                    ]
                ]
            ]
        ];

        $deck = ['invalid_card'];

        $this->dataMock->method('get')
            ->willReturnMap([
                ['players', null, $players],
                ['deck_of_cards', null, $deck],
            ]);

        $this->assertFalse($addCardPlayer->addCard($this->dataMock, 'player1', 'hand1'));
    }

    public function testActivateNextFindsNextHand(): void
    {
        $addCardPlayer = new AddCardPlayer($this->emMock, $this->securityMock, $this->decideWinnerMock);

        $players = [
            'player1' => [
                'hands' => [
                    'hand1' => ['status' => 'active'],
                    'hand2' => ['status' => 'waiting'],
                ]
            ]
        ];

        $this->dataMock->method('get')->willReturn($players);
        $this->dataMock->expects($this->atLeastOnce())->method('set');
        $this->dataMock->expects($this->once())->method('save');

        $result = $addCardPlayer->activateNext($this->dataMock, 'player1', 'hand1');
        $this->assertTrue($result);
    }

    public function testActivateNextReturnsFalseIfNoWaitingHand(): void
    {
        $addCardPlayer = new AddCardPlayer($this->emMock, $this->securityMock, $this->decideWinnerMock);

        $players = [
            'player1' => [
                'hands' => [
                    'hand1' => ['status' => 'active'],
                    'hand2' => ['status' => 'stand'],
                ]
            ]
        ];

        $this->dataMock->method('get')->willReturn($players);
        $this->dataMock->expects($this->atLeastOnce())->method('set');
        $this->dataMock->expects($this->once())->method('save');

        $result = $addCardPlayer->activateNext($this->dataMock, 'player1', 'hand1');
        $this->assertFalse($result);
    }

    public function testCheckAndHandleGameOver(): void
    {
        $addCardPlayer = new AddCardPlayer($this->emMock, $this->securityMock, $this->decideWinnerMock);

        $players = [
            'player1' => [
                'hands' => [
                    'hand1' => ['status' => 'active']
                ]
            ]
        ];

        $this->dataMock->method('get')->willReturn($players);
        $this->dataMock->expects($this->atLeastOnce())->method('set');
        $this->dataMock->expects($this->once())->method('save');

        $result = $addCardPlayer->checkAndHandleGameOver($this->dataMock, 'player1', 'hand1');
        $this->assertTrue($result);
    }

    public function testAddCardReturnsFalseIfNoCardsKey(): void
    {
        $addCardPlayer = new AddCardPlayer($this->emMock, $this->securityMock, $this->decideWinnerMock);
    
        $players = [
            'player1' => [
                'hands' => [
                    'hand1' => []
                ]
            ]
        ];
    
        $this->dataMock->method('get')
            ->willReturnMap([
                ['players', null, $players],
                ['deck_of_cards', null, [['value' => '7', 'suit' => 'spades']]]
            ]);
    
        $this->assertFalse($addCardPlayer->addCard($this->dataMock, 'player1', 'hand1'));
    }

    public function testActivateNextSkipsIfNoHandsKey(): void
    {
        $addCardPlayer = new AddCardPlayer($this->emMock, $this->securityMock, $this->decideWinnerMock);
    
        $players = [
            'player1' => []
        ];
    
        $this->dataMock->method('get')
            ->willReturnMap([
                ['players', null, $players]
            ]);
    
        $this->assertFalse($addCardPlayer->activateNext($this->dataMock, 'player1', 'hand1'));
    }
}