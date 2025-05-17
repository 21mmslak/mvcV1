<?php

use App\BlackJack\BlackJackRender;
use App\BlackJack\BlackJackSession;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

class BlackJackRenderTest extends TestCase
{
    public function testSimpleRenderGameStart(): void
    {
        $twig = $this->createStub(Environment::class);
        $state = $this->createStub(BlackJackSession::class);
        $session = $this->createStub(SessionInterface::class);

        $twig->method('render')->willReturn('rendered_game_start');
        $state->method('getCoins')->willReturn(100);

        $renderer = new BlackJackRender($twig, $state);
        $response = $renderer->renderGameStart([], [], 0, 0, false, $session);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('rendered_game_start', $response->getContent());
    }

    public function testSimpleRenderWinner(): void
    {
        $twig = $this->createStub(Environment::class);
        $state = $this->createStub(BlackJackSession::class);
        $session = $this->createStub(SessionInterface::class);

        $twig->method('render')->willReturn('rendered_winner');
        $state->method('getCoins')->willReturn(100);

        $renderer = new BlackJackRender($twig, $state);
        $response = $renderer->renderWinner('player', [], [], 0, 0, $session);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('rendered_winner', $response->getContent());
    }

    public function testSimpleRenderGameSplit(): void
    {
        $twig = $this->createStub(Environment::class);
        $state = $this->createStub(BlackJackSession::class);
        $session = $this->createStub(SessionInterface::class);

        $twig->method('render')->willReturn('game_split_html');
        $state->method('getCoins')->willReturn(100);

        $renderer = new BlackJackRender($twig, $state);
        $response = $renderer->renderGameSplit([], 10, [], [], 5, 10, 'hand1', $session);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('game_split_html', $response->getContent());
    }

    public function testSimpleRenderWinnerSplit(): void
    {
        $twig = $this->createStub(Environment::class);
        $state = $this->createStub(BlackJackSession::class);
        $session = $this->createStub(SessionInterface::class);

        $twig->method('render')->willReturn('winner_split_html');
        $state->method('getCoins')->willReturn(50);

        $renderer = new BlackJackRender($twig, $state);
        $response = $renderer->renderWinnerSplit([], 20, [], [], 21, 18, 'dealer', 'player', $session);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('winner_split_html', $response->getContent());
    }
} 
