<?php

namespace App\Card;

use PHPUnit\Framework\TestCase;
use App\Card\Card;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class CardTest extends TestCase
{
    public function testGetValue(): void
    {
        $card = new Card("spades", "Q");
        $this->assertSame("Q", $card->getValue());
    }

    public function testGetSuit(): void
    {
        $card = new Card("spades", "Q");
        $this->assertSame("spades", $card->getSuit());
    }

    public function testGetAsString(): void
    {
        $card = new Card("hearts", "A");
        $this->assertSame("A of hearts", $card->getAsString());
    }
}