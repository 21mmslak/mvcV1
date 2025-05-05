<?php

namespace App\Card;

use PHPUnit\Framework\TestCase;
use App\Card\CardHand;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class CardHandTest extends TestCase
{
    public function testAddCardToHandAndCount(): void
    {
        $hand = new CardHand();
        $card = new Card("spades", "Q");

        $hand->add($card);
        $cards = $hand->getCards();
        $count = $hand->count();

        $this->assertCount(1, $cards);
        $this->assertInstanceOf(Card::class, $cards[0]);
        $this->assertSame("Q", $cards[0]->getValue());
        $this->assertEquals(1, $count);
    }

    public function testGetValuesReturnsCardValues(): void
    {
        $hand = new CardHand();

        $hand->add(new Card("hearts", "A"));
        $hand->add(new Card("spades", "10"));
        $hand->add(new Card("clubs", "7"));

        $expected = ["A", "10", "7"];
        $this->assertSame($expected, $hand->getValues());
    }
}
