<?php

namespace App\BlackJack;

use PHPUnit\Framework\TestCase;
use App\BlackJack\BlackJack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class BlackJackTest extends TestCase
{
    public function testStartGameStoresDeckInSession(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $blackjack = new BlackJack();

        $cards = $blackjack->startGame($session);

        $this->assertCount(52, $cards);
        $this->assertTrue($session->has('shuffled_deck'));
        $this->assertEquals($cards, $session->get('shuffled_deck'));
    }

    public function testDeckIsShuffled(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $blackjack = new BlackJack();

        $cards1 = $blackjack->startGame($session);
        $cards2 = $blackjack->startGame($session);

        $this->assertNotEquals($cards1, $cards2, "Kortleken borde blandas olika varje gång.");
    }
}
