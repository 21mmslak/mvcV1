<?php

namespace App\Tests\Project;

use App\Project\DecideWinner;
use App\Project\Data;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\User;
use App\Entity\Scoreboard;

class DecideWinnerTest extends TestCase
{
    public function testDecideWinnerHandlesGameStarted(): void
    {
        $securityMock = $this->createMock(Security::class);
        $securityMock->method('getUser')->willReturn(new User());

        $emMock = $this->createMock(EntityManagerInterface::class);

        $dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'set', 'save'])
            ->getMock();

        $dataMock->method('get')
            ->willReturnMap([
                ['game_started', null, true],
                ['dealer_points', null, 0],
                ['players', null, []],
                ['coins', null, 5000]
            ]);

        $dataMock->expects($this->atLeastOnce())->method('set');
        $dataMock->expects($this->atLeastOnce())->method('save');

        $decideWinner = new DecideWinner($securityMock, $emMock);
        $decideWinner->decideWinner($dataMock);

        $this->assertTrue(true);
    }

    public function testDecideWinnerLoopCoversPlayers(): void
    {
        $securityMock = $this->createMock(Security::class);
        $securityMock->method('getUser')->willReturn(new User());

        $emMock = $this->createMock(EntityManagerInterface::class);

        $dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'set', 'save'])
            ->getMock();

        $dataMock->method('get')
            ->willReturnMap([
                ['game_started', null, false],
                ['dealer_points', null, 18],
                ['players', null, [
                    'player1' => [
                        'hands' => [
                            'hand1' => [
                                'bet' => 20,
                                'points' => 19,
                                'cards' => [
                                    ['value' => 'K', 'suit' => 'hearts'],
                                    ['value' => '9', 'suit' => 'spades']
                                ]
                            ]
                        ]
                    ]
                ]],
                ['coins', null, 1000]
            ]);

        $dataMock->expects($this->atLeastOnce())->method('set');
        $dataMock->expects($this->atLeastOnce())->method('save');

        $decideWinner = new DecideWinner($securityMock, $emMock);
        $decideWinner->decideWinner($dataMock);

        $this->assertTrue(true);
    }

    public function testGetUserCoinsReturnsScoreboardValue(): void
    {
        $securityMock = $this->createMock(Security::class);

        $scoreboardMock = $this->createMock(Scoreboard::class);
        $scoreboardMock->method('getCoins')->willReturn(2000);

        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('findOneBy')->willReturn($scoreboardMock);

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getRepository')->willReturn($repoMock);

        $dataMock = $this->createMock(Data::class);
        $dataMock->method('get')->willReturn(5000);

        $decideWinner = new DecideWinner($securityMock, $emMock);
        $result = $this->invokePrivate($decideWinner, 'getUserCoins', [new User(), $dataMock]);

        $this->assertEquals(2000, $result);
    }

    public function testGetUserCoinsReturnsFallbackCoins(): void
    {
        $securityMock = $this->createMock(Security::class);

        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('findOneBy')->willReturn(null);

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getRepository')->willReturn($repoMock);

        $dataMock = $this->createMock(Data::class);
        $dataMock->method('get')->willReturn(4000);

        $decideWinner = new DecideWinner($securityMock, $emMock);
        $result = $this->invokePrivate($decideWinner, 'getUserCoins', [new User(), $dataMock]);

        $this->assertEquals(4000, $result);
    }

    private function invokePrivate(object $object, string $methodName, array $args = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    public function testEvaluateHandBlackjack(): void
    {
        $securityMock = $this->createMock(Security::class);
        $emMock = $this->createMock(EntityManagerInterface::class);
        $decideWinner = new DecideWinner($securityMock, $emMock);

        $hand = [
            'points' => 21,
            'cards' => [
                ['value' => 'A', 'suit' => 'hearts'],
                ['value' => 'K', 'suit' => 'spades']
            ]
        ];
        $dealerPoints = 18;
        $bet = 100;
        $coins = 5000;

        $result = $this->invokePrivate($decideWinner, 'evaluateHand', [$hand, $dealerPoints, $bet, $coins]);

        $this->assertEquals((int) round($bet * 1.5), $result['coinsDelta']);
        $this->assertStringContainsString('Blackjack! Player wins', $result['message']);
    }

    public function testEvaluateHandBust(): void
    {
        $securityMock = $this->createMock(Security::class);
        $emMock = $this->createMock(EntityManagerInterface::class);
        $decideWinner = new DecideWinner($securityMock, $emMock);

        $hand = [
            'points' => 22,
            'cards' => [
                ['value' => 'K', 'suit' => 'hearts'],
                ['value' => 'K', 'suit' => 'spades'],
                ['value' => '2', 'suit' => 'spades']
            ]
        ];
        $dealerPoints = 18;
        $bet = 100;
        $coins = 5000;

        $result = $this->invokePrivate($decideWinner, 'evaluateHand', [$hand, $dealerPoints, $bet, $coins]);

        $this->assertEquals(-100, $result['coinsDelta']);
        $this->assertStringContainsString('Dealer wins (bust).', $result['message']);
    }

    public function testDealerWin(): void
    {
        $securityMock = $this->createMock(Security::class);
        $emMock = $this->createMock(EntityManagerInterface::class);
        $decideWinner = new DecideWinner($securityMock, $emMock);

        $hand = [
            'points' => 20,
            'cards' => [
                ['value' => 'K', 'suit' => 'hearts'],
                ['value' => 'K', 'suit' => 'spades']
            ]
        ];
        $dealerPoints = 21;
        $bet = 100;
        $coins = 5000;

        $result = $this->invokePrivate($decideWinner, 'evaluateHand', [$hand, $dealerPoints, $bet, $coins]);

        $this->assertEquals(-100, $result['coinsDelta']);
        $this->assertStringContainsString('Dealer wins.', $result['message']);
    }

    public function testDraw(): void
    {
        $securityMock = $this->createMock(Security::class);
        $emMock = $this->createMock(EntityManagerInterface::class);
        $decideWinner = new DecideWinner($securityMock, $emMock);

        $hand = [
            'points' => 20,
            'cards' => [
                ['value' => 'K', 'suit' => 'hearts'],
                ['value' => 'K', 'suit' => 'spades']
            ]
        ];
        $dealerPoints = 20;
        $bet = 100;
        $coins = 5000;

        $result = $this->invokePrivate($decideWinner, 'evaluateHand', [$hand, $dealerPoints, $bet, $coins]);

        $this->assertEquals(0, $result['coinsDelta']);
        $this->assertStringContainsString('Draw.', $result['message']);
    }

    public function testUpdateCoinsWithScoreboard(): void
    {
        $securityMock = $this->createMock(Security::class);
        $emMock = $this->createMock(EntityManagerInterface::class);
        
        $scoreboardMock = $this->createMock(Scoreboard::class);
        $scoreboardMock->expects($this->once())->method('setCoins')->with(1500);
        $emMock->expects($this->once())->method('persist')->with($scoreboardMock);
        $emMock->expects($this->once())->method('flush');
        
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('findOneBy')->willReturn($scoreboardMock);
        $emMock->method('getRepository')->willReturn($repoMock);
        
        $dataMock = $this->createMock(Data::class);
        $dataMock->expects($this->never())->method('set');
        
        $user = new User();
        $decideWinner = new DecideWinner($securityMock, $emMock);
        
        $this->invokePrivate($decideWinner, 'updateCoins', [$user, 1500, $dataMock]);
    }  
}