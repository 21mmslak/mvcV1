<?php

namespace App\Tests\Project;

use App\Project\AddCardDealer;
use App\Project\Data;
use App\Project\Rules;
use PHPUnit\Framework\TestCase;

class AddCardDealerTest extends TestCase
{
    public function testAddCardDealerWithNormalDeck()
    {
        $data = $this->createMock(Data::class);
        $rules = new Rules();

        $data->method('get')
             ->willReturnMap([
                 ['dealer_card_one', [], [['value' => '5', 'suit' => 'hearts']]],
                 ['dealer_card_two', [], [['value' => '6', 'suit' => 'spades']]],
                 ['dealer_cards', [], []],
                 ['deck_of_cards', [], [
                     ['value' => '3', 'suit' => 'clubs'],
                     ['value' => '4', 'suit' => 'diamonds'],
                 ]]
             ]);

        $data->expects($this->atLeastOnce())->method('set');
        $data->expects($this->once())->method('save');

        $dealer = new AddCardDealer();
        $dealer->addCardDealer($data);
    }

    public function testAddCardDealerWithNullDeck()
    {
        $data = $this->createMock(Data::class);

        $data->method('get')
             ->willReturnMap([
                 ['dealer_card_one', [], [['value' => '5', 'suit' => 'hearts']]],
                 ['dealer_card_two', [], [['value' => '6', 'suit' => 'spades']]],
                 ['dealer_cards', [], []],
                 ['deck_of_cards', [], null]
             ]);

        $data->expects($this->atLeastOnce())->method('set');
        $data->expects($this->once())->method('save');

        $dealer = new AddCardDealer();
        $dealer->addCardDealer($data);
    }

    public function testAddCardDealerWithFalseDeck()
    {
        $data = $this->createMock(Data::class);

        $data->method('get')
             ->willReturnMap([
                 ['dealer_card_one', [], [['value' => '5', 'suit' => 'hearts']]],
                 ['dealer_card_two', [], [['value' => '6', 'suit' => 'spades']]],
                 ['dealer_cards', [], []],
                 ['deck_of_cards', [], false]
             ]);

        $data->expects($this->atLeastOnce())->method('set');
        $data->expects($this->once())->method('save');

        $dealer = new AddCardDealer();
        $dealer->addCardDealer($data);
    }

    public function testAddCardDealerWithIntegerDeck()
    {
        $data = $this->createMock(Data::class);

        $data->method('get')
             ->willReturnMap([
                 ['dealer_card_one', [], [['value' => '5', 'suit' => 'hearts']]],
                 ['dealer_card_two', [], [['value' => '6', 'suit' => 'spades']]],
                 ['dealer_cards', [], []],
                 ['deck_of_cards', [], 123]
             ]);

        $data->expects($this->atLeastOnce())->method('set');
        $data->expects($this->once())->method('save');

        $dealer = new AddCardDealer();
        $dealer->addCardDealer($data);
    }

    public function testAddCardDealerCoversBreak()
    {
        $dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'set', 'save'])
            ->getMock();
    
        $dataMock->method('get')->willReturnCallback(function ($key, $default = null) {
            return match ($key) {
                'dealer_card_one' => [['value' => '5', 'suit' => 'hearts']],
                'dealer_card_two' => [['value' => '6', 'suit' => 'diamonds']],
                'dealer_cards' => [],
                'deck_of_cards' => [['value' => '9', 'suit' => 'clubs'], ['value' => '8', 'suit' => 'spades']],
                default => $default,
            };
        });
    
        $dataMock->expects($this->atLeastOnce())->method('set');
        $dataMock->expects($this->once())->method('save');
    
        $addCardDealer = new AddCardDealer();
    
        $addCardDealer->addCardDealer($dataMock, 100);
    
        $this->assertTrue(true);
    }
}