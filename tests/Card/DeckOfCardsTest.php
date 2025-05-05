<?php

namespace App\Card;

use PHPUnit\Framework\TestCase;
use App\Card\DeckOfCards;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class DeckOfCardsTest extends TestCase
{
    public function testDrawReducesDeckSize(): void
    {
        $deck = new DeckOfCards();

        $initialCount = count($deck->getDeck());
        $drawnCard = $deck->draw();

        $this->assertInstanceOf(CardGrafic::class, $drawnCard);
        $this->assertCount($initialCount - 1, $deck->getDeck());
        $this->assertSame($initialCount - 1, $deck->remaining());
    }

    public function testResetRestoresFullDeck(): void
    {
        $deck = new DeckOfCards();

        $deck->draw();
        $deck->draw();

        $this->assertLessThan(52, count($deck->getDeck()));
        $deck->reset();

        $this->assertCount(52, $deck->getDeck());
    }
}
