<?php

namespace App\BlackJack;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use App\BlackJack\BlackJackRules;

class BlackJackRulesTest extends TestCase
{
    public function testCountPointsHandlesNumberCards(): void
    {
        $obj = new BlackJackRules();
        $hand = [
            ['value' => '2'],
            ['value' => '3'],
            ['value' => '4'],
        ];

        $points = $obj->countPoints($hand);
        $this->assertSame(9, $points);
    }

    public function testCountPointsHandlesFaceCards(): void
    {
        $obj = new BlackJackRules();
        $hand = [
            ['value' => 'J'],
            ['value' => 'Q'],
            ['value' => 'K'],
        ];

        $points = $obj->countPoints($hand);
        $this->assertSame(30, $points);
    }

    public function testCountPointsHandlesAces(): void
    {
        $obj = new BlackJackRules();
        $hand = [
            ['value' => 'A'],
            ['value' => '9'],
        ];

        $points = $obj->countPoints($hand);
        $this->assertSame(20, $points);
    }

    public function testGetIntFromSessionReturnsZeroWhenValueIsNotNumeric(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('test_key', 'non-numeric');
        $obj = new class extends BlackJackRules {
            public function publicGetIntFromSession($session, $key) {
                return $this->getIntFromSession($session, $key);
            }
        };

        $this->assertSame(0, $obj->publicGetIntFromSession($session, 'test_key'));
    }

    public function testCountPointsReducesAceIfOver21(): void
    {
        $obj = new BlackJackRules();
        $hand = [
            ['value' => 'A'],
            ['value' => 'K'],
            ['value' => '5'],
        ];

        $points = $obj->countPoints($hand);
        $this->assertSame(16, $points);
    }

    public function testCheckOverTrue(): void
    {
        $obj = new BlackJackRules();
        $this->assertTrue($obj->checkOver(21));
        $this->assertTrue($obj->checkOver(22));
    }

    public function testCheckOverFalse(): void
    {
        $obj = new BlackJackRules();
        $this->assertFalse($obj->checkOver(20));
    }

    public function testDecideWinnerPlayerLoses(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('coins', 50);

        $obj = new BlackJackRules();
        $result = $obj->decideWinner(18, 22, $session);

        $this->assertSame("Dealer Win! Player lose :(", $result);
        $this->assertSame(40, $session->get('coins'));
    }

    public function testDecideWinnerDealerLoses(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('coins', 10);

        $obj = new BlackJackRules();
        $result = $obj->decideWinner(22, 20, $session);

        $this->assertSame("Player Win! Dealer lose :)", $result);
        $this->assertSame(20, $session->get('coins'));
    }

    public function testDecideWinnerPlayerBeatsDealer(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('coins', 5);

        $obj = new BlackJackRules();
        $result = $obj->decideWinner(18, 19, $session);

        $this->assertSame("Player Win! Dealer lose :)", $result);
        $this->assertSame(15, $session->get('coins'));
    }

    public function testDecideWinnerDealerBeatsPlayer(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('coins', 15);

        $obj = new BlackJackRules();
        $result = $obj->decideWinner(20, 19, $session);

        $this->assertSame("Dealer Win! Player lose :(", $result);
        $this->assertSame(5, $session->get('coins'));
    }

    public function testDecideWinnerDraw(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('coins', 30);

        $obj = new BlackJackRules();
        $result = $obj->decideWinner(18, 18, $session);

        $this->assertSame("Draw! Nobody wins.", $result);
        $this->assertSame(30, $session->get('coins'));
    }
}