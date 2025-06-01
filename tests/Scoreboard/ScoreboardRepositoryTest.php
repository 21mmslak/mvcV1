<?php

namespace App\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Scoreboard;

class ScoreboardRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $em = null;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get('doctrine')->getManager();

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    public function testScoreboardCreation(): void
    {
        $user = new User();
        $user->setUsername('testuser');
        $user->setPassword('password');
        $this->em->persist($user);
        $this->em->flush();

        $scoreboard = new Scoreboard();
        $scoreboard->setUser($user);
        $scoreboard->setCoins(5000);
        $this->em->persist($scoreboard);
        $this->em->flush();

        $found = $this->em->getRepository(Scoreboard::class)->findOneBy(['user' => $user]);

        $this->assertNotNull($found);
        $this->assertEquals(5000, $found->getCoins());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
        $this->em = null;
    }
}