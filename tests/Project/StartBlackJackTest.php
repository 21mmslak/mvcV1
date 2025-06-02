<?php

namespace App\Tests\Project;

use App\Project\DecideWinner;
use App\Project\Data;
use App\Project\StartBlackJack;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\User;
use App\Entity\Scoreboard;

class StartBlackJackTest extends TestCase
{
    public function testStartBlackjack(): void
    {
        $securityMock = $this->createMock(Security::class);
        $emMock = $this->createMock(EntityManagerInterface::class);
        $dataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'set', 'save'])
            ->getMock();

        $dataMock->expects($this->atLeastOnce())->method('set');
        $dataMock->expects($this->atLeastOnce())->method('save');

       $startBlackJack = new StartBlackJack($securityMock, $emMock);
       $startBlackJack->start($dataMock);

        $this->assertTrue(true);
    }

    public function testInitializePlayerCoinsWithUserScoreboard()
    {
        $user = new User();

        $securityMock = $this->createMock(Security::class);
        $securityMock->method('getUser')->willReturn($user);

        $scoreboardMock = $this->createMock(Scoreboard::class);
        $scoreboardMock->method('getCoins')->willReturn(5000);

        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('findOneBy')->willReturn($scoreboardMock);

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getRepository')->willReturn($repoMock);

        $dataMock = $this->createMock(Data::class);

        $startBlackJack = new StartBlackJack($securityMock, $emMock);

        $method = new \ReflectionMethod(StartBlackJack::class, 'initializePlayerCoins');
        $method->setAccessible(true);
        $coins = $method->invoke($startBlackJack, $dataMock);

        $this->assertEquals(5000, $coins);
    }

    public function testInitializePlayerCoinsWithoutUser()
    {
        $securityMock = $this->createMock(Security::class);
        $securityMock->method('getUser')->willReturn(null);

        $emMock = $this->createMock(EntityManagerInterface::class);
        $dataMock = $this->createMock(Data::class);
        $dataMock->method('get')->willReturn(3000);

        $startBlackJack = new StartBlackJack($securityMock, $emMock);

        $method = new \ReflectionMethod(StartBlackJack::class, 'initializePlayerCoins');
        $method->setAccessible(true);
        $coins = $method->invoke($startBlackJack, $dataMock);

        $this->assertEquals(3000, $coins);
    }

    public function testInitializePlayerCoinsCreatesScoreboardIfMissing()
    {
        $user = new User();
        $securityMock = $this->createMock(Security::class);
        $securityMock->method('getUser')->willReturn($user);

        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('findOneBy')->willReturn(null);

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getRepository')->willReturn($repoMock);

        $emMock->expects($this->once())->method('persist')->with($this->isInstanceOf(Scoreboard::class));
        $emMock->expects($this->once())->method('flush');

        $dataMock = $this->createMock(Data::class);

        $startBlackJack = new StartBlackJack($securityMock, $emMock);

        $method = new \ReflectionMethod(StartBlackJack::class, 'initializePlayerCoins');
        $method->setAccessible(true);
        $coins = $method->invoke($startBlackJack, $dataMock);

        $this->assertEquals(5000, $coins);
    }
}