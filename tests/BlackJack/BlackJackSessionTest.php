<?php

use App\BlackJack\BlackJackSession;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class BlackJackSessionTest extends TestCase
{
    public function testGetCoinsReturnsInt(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')
            ->with('coins')
            ->willReturn(250);

        $bjSession = new BlackJackSession();
        $this->assertSame(250, $bjSession->getCoins($session));
    }

    public function testGetCoinsThrowsExceptionIfNotInt(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->with('coins')->willReturn("not-int");

        $bjSession = new BlackJackSession();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected int for coins');

        $bjSession->getCoins($session);
    }

    public function testGetDealerCardsThrowsExceptionIfNotArray(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')
            ->with('dealer_cards', [])
            ->willReturn("not-an-array");

        $bjSession = new BlackJackSession();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Expected array for dealer_cards");

        $bjSession->getDealerCards($session);
    }

    public function testGetDeckThrowsExceptionIfNotArray(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')
            ->with('shuffled_deck', [])
            ->willReturn("not-an-array");

        $bjSession = new BlackJackSession();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Expected array for shuffled_deck");

        $bjSession->getDeck($session);
    }

    public function testGetHandThrowsExceptionIfNotArray(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')
            ->with('hand1', [])
            ->willReturn("not-an-array");

        $bjSession = new BlackJackSession();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Expected array for hand1");

        $bjSession->getHand($session, 'hand1');
    }

    public function testSetCoins(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('set')
            ->with('coins', 200);

        $blackJackSession = new BlackJackSession();
        $blackJackSession->setCoins($session, 200);
    }

    public function testGetStringThrowsExceptionIfNotString(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')
            ->with('active_hand')
            ->willReturn(123);

        $bjSession = new BlackJackSession();

        $reflection = new \ReflectionClass($bjSession);
        $method = $reflection->getMethod('getString');
        $method->setAccessible(true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Expected string for active_hand");

        $method->invoke($bjSession, $session, 'active_hand');
    }

    public function testGetPlayerCardsThrowsExceptionIfNotArray(): void
    {
        $session = $this->createStub(SessionInterface::class);
        $session->method('get')->willReturn("not-an-array");

        $bjSession = new BlackJackSession();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Expected array for player_cards");

        $bjSession->getPlayerCards($session);
    }

    public function testGetIntThrowsException(): void
    {
        $session = $this->createStub(SessionInterface::class);
        $session->method('get')->willReturn("not-an-int");

        $bjSession = new BlackJackSession();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Expected int for score");

        $reflection = new \ReflectionClass($bjSession);
        $method = $reflection->getMethod('getInt');
        $method->setAccessible(true);
        $method->invoke($bjSession, $session, "score");
    }

    public function testGetReturnsExpectedValue(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')
            ->with('coins', 0)
            ->willReturn(200);
    
        $bjSession = new BlackJackSession();
        $result = $bjSession->get($session, 'coins', 0);
    
        $this->assertSame(200, $result);
    }

    public function testGetReturnsDefault(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')
            ->with('missing_key', 42)
            ->willReturn(42);

        $bjSession = new BlackJackSession();
        $result = $bjSession->get($session, 'missing_key', 42);

        $this->assertSame(42, $result);
    }
}