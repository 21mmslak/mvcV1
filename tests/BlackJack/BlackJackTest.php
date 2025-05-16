<?php

namespace App\Tests\BlackJack;

use PHPUnit\Framework\TestCase;
use App\BlackJack\BlackJack;

class BlackJackTest extends TestCase
{
    public function testStartGameReturnsArrayWith52Cards(): void
    {
        $blackjack = new BlackJack();
        $deck = $blackjack->startGame();

        $this->assertIsArray($deck);
        $this->assertCount(52, $deck);

        foreach ($deck as $card) {
            $this->assertIsArray($card);
            $this->assertArrayHasKey('card', $card);
            $this->assertArrayHasKey('value', $card);
            $this->assertArrayHasKey('suit', $card);
        }
    }
}