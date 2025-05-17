<?php

namespace App\Tests\BlackJack;

use PHPUnit\Framework\TestCase;
use App\Card\Card;
use App\BlackJack\BlackJackGameManager;
use App\BlackJack\BlackJack;
use App\BlackJack\BlackJackRules;
use App\BlackJack\BlackJackSession;
use App\BlackJack\BlackJackService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class BlackJackGameManagerTest extends TestCase
{
    public function testResetGame(): void
    {
        $rules = $this->createMock(BlackJackRules::class);
        $state = $this->createMock(BlackJackSession::class);
        $service = $this->createMock(BlackJackService::class);
        $session = $this->createMock(SessionInterface::class);
    
        $session->expects($this->once())
            ->method('set')
            ->with('game_started', false);
    
        $session->expects($this->atLeastOnce())
            ->method('remove')
            ->with($this->isType('string'));
    
        $manager = new BlackJackGameManager($rules, $state, $service);
        $manager->resetGame($session);
    }

    public function testStartNewGameReturnsExpectedStructure(): void
    {
        $session = new Session(
            new MockArraySessionStorage()
        );

        $blackJack = new BlackJack();
        $rules = new BlackJackRules();
        $state = new BlackJackSession();
        $service = new BlackJackService($blackJack);

        $manager = new BlackJackGameManager($rules, $state, $service);

        $result = $manager->startNewGame($session);

        // $this->assertIsArray($result);
        $this->assertCount(2, $result['playerCards']);
        $this->assertCount(2, $result['dealerCards']);
        $this->assertIsInt($result['playerPoints']);
        $this->assertIsInt($result['dealerPoints']);
        $this->assertIsBool($result['isOver']);
    }

    public function testAddCardToPlayerReturnsExpectedArray(): void
    {
        $session = new Session(
            new MockArraySessionStorage()
        );
    
        $blackJack = new BlackJack();
        $rules = new BlackJackRules();
        $state = new BlackJackSession();
        $service = new BlackJackService($blackJack);
    
        $manager = new BlackJackGameManager($rules, $state, $service);
    
        $manager->startNewGame($session);
    
        $result = $manager->addCardToPlayer($session);
    
        // $this->assertIsArray($result);
        $this->assertArrayHasKey('playerCards', $result);
        $this->assertArrayHasKey('dealerCards', $result);
        $this->assertArrayHasKey('dealerPoints', $result);
        $this->assertArrayHasKey('playerPoints', $result);
        $this->assertArrayHasKey('isOver', $result);
    }

    public function testAddCardToSplitHandReturnsExpectedKeys(): void
    {
        $session = new Session(
            new MockArraySessionStorage()
        );

        $blackJack = new BlackJack();
        $rules = new BlackJackRules();
        $state = new BlackJackSession();
        $service = new BlackJackService($blackJack);

        $manager = new BlackJackGameManager($rules, $state, $service);

        $manager->startNewGame($session);
        $manager->split($session);

        $result = $manager->addCardToSplitHand($session);

        // $this->assertIsArray($result);
        foreach ([
            'dealerCards', 'dealerPoints', 'hand1', 'hand2',
            'playerPoints1', 'playerPoints2', 'activeHand', 'shouldRedirect'
        ] as $key) {
            $this->assertArrayHasKey($key, $result);
        }
    }

    public function testHand1TriggersSwitchToHand2(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $blackJack = new BlackJack();

        $rules = $this->createMock(BlackJackRules::class);
        $rules->method('countPoints')->willReturn(21);

        $state = new BlackJackSession();
        $service = new BlackJackService($blackJack);
        $manager = new BlackJackGameManager($rules, $state, $service);

        $manager->startNewGame($session);
        $manager->split($session);

        $hand1 = [
            ['value' => 'K', 'suit' => 'hearts', 'card' => 'K♥'],
            ['value' => 'A', 'suit' => 'spades', 'card' => 'A♠']
        ];
        $deck = [
            new Card('A', 'clubs')
        ];

        $state->setHand($session, "hand1", $hand1);
        $state->setDeck($deck, $session);
        $state->setActiveHand($session, "hand1");

        $manager->addCardToSplitHand($session);

        $this->assertSame("hand2", $state->getActiveHand($session));
    }

    public function testStandSplitReturnsNullWhenActiveHandIsHand1(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $rules = $this->createMock(BlackJackRules::class);
        $state = new BlackJackSession();
        $service = new BlackJackService(new BlackJack());
        $manager = new BlackJackGameManager($rules, $state, $service);
    
        $state->setActiveHand($session, "hand1");
    
        $result = $manager->standSplit($session);
    
        $this->assertNull($result);
        $this->assertSame("hand2", $state->getActiveHand($session));
    }

    public function testStandSplitReturnsResultWhenActiveHandIsHand2(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $rules = $this->createMock(BlackJackRules::class);
        $rules->method('countPoints')->willReturnOnConsecutiveCalls(10, 17);
        $rules->method('decideWinner')->willReturn('dealer');
    
        $state = new BlackJackSession();
        $service = new BlackJackService(new BlackJack());
        $manager = new BlackJackGameManager($rules, $state, $service);
    
        $state->setActiveHand($session, "hand2");
        $state->setDeck([
            new Card('7', 'clubs')
        ], $session);
        $state->setDealerCards([
            ['value' => '5', 'suit' => 'hearts', 'card' => '5♥']
        ], $session);
        $state->setPoints($session, "player_points_1", 18);
        $state->setPoints($session, "player_points_2", 19);
        $state->setHand($session, "hand1", [['value' => '9', 'suit' => '♦', 'card' => '9♦']]);
        $state->setHand($session, "hand2", [['value' => '10', 'suit' => '♣', 'card' => '10♣']]);
    
        $result = $manager->standSplit($session);
    
        $this->assertIsArray($result);
        $this->assertSame('dealer', $result['winner1']);
        $this->assertSame('dealer', $result['winner2']);
    }

    public function testStandReturnsExpectedStructure(): void
    {
        $session = new Session(new MockArraySessionStorage());
    
        $rules = $this->createMock(BlackJackRules::class);
        $rules->method('countPoints')->willReturnOnConsecutiveCalls(10, 17);
        $rules->method('decideWinner')->willReturn('player');
    
        $state = new BlackJackSession();
        $service = new BlackJackService(new BlackJack());
        $manager = new BlackJackGameManager($rules, $state, $service);
    
        $state->setDeck([
            new Card('7', 'clubs')
        ], $session);
    
        $state->setDealerCards([
            ['value' => '5', 'suit' => 'hearts', 'card' => '5♥']
        ], $session);
    
        $state->setPlayerCards([
            new Card('10', 'spades')
        ], $session);
    
        $state->setPoints($session, 'player_points', 20);
    
        $result = $manager->stand($session);
    
        $this->assertArrayHasKey('dealerCards', $result);
        $this->assertArrayHasKey('dealerPoints', $result);
        $this->assertArrayHasKey('playerCards', $result);
        $this->assertArrayHasKey('playerPoints', $result);
        $this->assertSame('player', $result['winner']);
    }

    public function testHand2TriggersRedirect(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $blackJack = new BlackJack();
        $rules = new BlackJackRules();
        $state = new BlackJackSession();
        $service = new BlackJackService($blackJack);
        $manager = new BlackJackGameManager($rules, $state, $service);

        $manager->startNewGame($session);
        $manager->split($session);

        $state->setActiveHand($session, "hand2");

        $hand2 = [
            ['value' => 'K', 'suit' => 'hearts', 'card' => 'K♥'],
            ['value' => 'Q', 'suit' => 'spades', 'card' => 'Q♠']
        ];
        $deck = [
            ['value' => 'A', 'suit' => 'clubs', 'card' => 'A♣']
        ];

        $state->setHand($session, "hand2", $hand2);
        $state->setDeck($deck, $session);

        $result = $manager->addCardToSplitHand($session);

        $this->assertTrue($result['shouldRedirect']);
    }

    public function testStartNewGameReturnsExpectedKeys(): void
    {
        $rules = $this->createMock(BlackJackRules::class);
        $state = $this->createMock(BlackJackSession::class);
        $service = $this->createMock(BlackJackService::class);
        $session = $this->createMock(SessionInterface::class);
    
        $session->method('get')->willReturn(true);
        $state->method('getPlayerCards')->willReturn([]);
        $state->method('getDealerCards')->willReturn([]);
        $state->method('getPoints')->willReturn(0);
        $rules->method('checkOver')->willReturn(false);
    
        $manager = new BlackJackGameManager($rules, $state, $service);
        $result = $manager->startNewGame($session);
    
        $this->assertArrayHasKey('playerCards', $result);
        $this->assertArrayHasKey('dealerCards', $result);
        $this->assertArrayHasKey('dealerPoints', $result);
        $this->assertArrayHasKey('playerPoints', $result);
        $this->assertArrayHasKey('isOver', $result);
    }
}
