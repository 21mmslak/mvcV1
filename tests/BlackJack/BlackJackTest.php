<?php

namespace App\BlackJack;

use PHPUnit\Framework\TestCase;
use App\BlackJack\BlackJack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class BlackJackTest extends TestCase
{
    public function testStartGameReturnsExpectedCardStructure(): void
    {
        $blackjack = new BlackJack();
        $result = $blackjack->startGame();

        $this->assertNotEmpty($result);

        $card = $result[0];
        $this->assertArrayHasKey('card', $card);
        $this->assertArrayHasKey('value', $card);
        $this->assertArrayHasKey('suit', $card);
    }

    public function testStartGameReturnsCardsWithExpectedKeys(): void
    {
        $blackjack = new BlackJack();
        $result = $blackjack->startGame();

        $this->assertNotEmpty($result);
        $firstCard = $result[0];

        $this->assertArrayHasKey('card', $firstCard);
        $this->assertArrayHasKey('value', $firstCard);
        $this->assertArrayHasKey('suit', $firstCard);
    }

    public function testStartGameReturns52Cards(): void
    {
        $blackjack = new BlackJack();
        $result = $blackjack->startGame();

        $this->assertCount(52, $result);
    }
}
