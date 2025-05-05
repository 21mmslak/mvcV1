<?php

namespace App\Controller;

use App\Controller\CardController;
use App\Card\DeckOfCards;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use LogicException;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;
use Exception;

use function pcov\start;

class CardControllerTest extends TestCase
{
    public function testRenderHome(): void
    {
        $controller = new class () extends CardController {
            /**
             * @param array<string, mixed> $parameters
             */
            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                return new Response("Rendering {$view}");
            }
        };

        $response = $controller->home();
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testSessionGet(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set("key", "value");

        $controller = new class () extends CardController {
            /**
             * @var array<string, mixed>
             */
            public array $renderedData = [];

            /**
             * @param array<string, mixed> $parameters
             */
            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->renderedData = $parameters;
                return new Response("rendered");
            }
        };

        $response = $controller->session($session);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame("rendered", $response->getContent());
        $this->assertArrayHasKey("session", $controller->renderedData);
        $this->assertSame(["key" => "value"], $controller->renderedData["session"]);
    }

    public function testSessionDestroy(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('key', 'value');

        $controller = new class () extends CardController {
            public string $redirectedRoute = '';
            /**
             * @var array<string, string[]>
             */
            public array $flashes = [];

            /**
             * @param array<string, mixed> $parameters
             */
            public function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
            {
                $this->redirectedRoute = $route;
                return new RedirectResponse("/$route", $status);
            }

            public function addFlash(string $type, mixed $message): void
            {
                $this->flashes[$type][] = $message;
            }
        };

        /** @var RedirectResponse $response */
        $response = $controller->sessionDelete($session);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/session', $response->headers->get('Location'));
        $this->assertEmpty($session->all());
        $this->assertArrayHasKey('notice', $controller->flashes);
        $this->assertContains('Session is now cleard!', $controller->flashes['notice']);
    }

    public function testRenderDeck(): void
    {

        $controller = new class () extends CardController {
            /**
             * @param array<string, mixed> $parameters
             */
            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                return new Response("Rendering {$view}");
            }
        };

        $response = $controller->cardDeck();
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testShuffleDeck(): void
    {
        $session = new Session(new MockArraySessionStorage());

        $controller = new class () extends CardController {
            public string $usedTemplate = '';
            /** @var array<string, mixed> */
            public array $params = [];

            /**
             * @param array<string, mixed> $parameters
             */
            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->usedTemplate = $view;
                $this->params = $parameters;
                return new Response("rendered");
            }
        };

        $response = $controller->deckShuffle($session);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered', $response->getContent());
        $this->assertEquals('card/shuffle.html.twig', $controller->usedTemplate);

        $cards = $session->get("shuffled_deck");
        $this->assertIsArray($cards);
        $this->assertCount(52, $cards);
        $this->assertArrayHasKey("suit", $cards[0]);
        $this->assertArrayHasKey("value", $cards[0]);
    }

    public function testDrawCard(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $deck = [];

        for ($i = 1; $i <= 3; $i++) {
            $deck[] = ["value" => (string)$i, "suit" => "hearts"];
        }

        $session->set("shuffled_deck", $deck);

        $controller = new class () extends CardController {
            public string $view = '';
            /** @var array<string, mixed> */
            public array $params = [];

            /**
             * @param array<string, mixed> $parameters
             */
            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->view = $view;
                $this->params = $parameters;
                return new Response("rendered");
            }
        };

        $response = $controller->drawCard($session);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals("rendered", $response->getContent());
        $this->assertEquals("card/draw.html.twig", $controller->view);
        $this->assertCount(1, $controller->params["drawn_cards"]);
        $this->assertEquals(2, $controller->params["cards_left"]);
    }

    public function testDrawCardThrowsExceptionWhenDeckIsInvalid(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set("shuffled_deck", "not-an-array");

        $controller = new CardController();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("No valid deck found in session");

        $controller->drawCard($session);
    }

    public function testDrawCardsThrowsLogicExceptionOnInvalidDeck(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set("shuffled_deck", "not-an-array");

        $controller = new CardController();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("No valid deck found in session");

        $controller->drawCards(1, $session);
    }

    public function testDrawCardsThrowsExceptionWhenNotEnoughCards(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set("shuffled_deck", [
            ["value" => "K", "suit" => "hearts"],
            ["value" => "A", "suit" => "spades"]
        ]);

        $controller = new CardController();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Cannot draw more cards than are left in the deck!");

        $controller->drawCards(3, $session);
    }

    public function testDrawCardsSuccessfullyDrawsCards(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set("shuffled_deck", [
            ["value" => "5", "suit" => "hearts"],
            ["value" => "K", "suit" => "clubs"],
            ["value" => "7", "suit" => "spades"]
        ]);

        $controller = new class () extends CardController {
            public string $usedTemplate = '';
            /** @var array{drawn_cards: list<array{value: string, suit: string}>, cards_left: int} */
            public array $params = [
                'drawn_cards' => [],
                'cards_left' => 0,
            ];

            /**
             * @param array{drawn_cards: list<array{value: string, suit: string}>, cards_left: int} $parameters
             */
            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->usedTemplate = $view;
                $this->params = $parameters;
                return new Response("rendered");
            }
        };

        $response = $controller->drawCards(2, $session);

        $this->assertEquals("rendered", $response->getContent());
        $this->assertSame("card/draw.html.twig", $controller->usedTemplate);
        $this->assertCount(2, $controller->params["drawn_cards"]);
        $this->assertEquals(1, $controller->params["cards_left"]);
    }

    public function testDrawCardsPlayersDistributesCards(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set("shuffled_deck", array_fill(0, 10, ["value" => "5", "suit" => "hearts"]));
    
        $controller = new class () extends CardController {
            public string $usedTemplate = '';
    
            /** 
             * @var array{playerHand: array<string, array<int, array<string, string>>>, cards_left: int} 
             */
            public array $params = [
                'playerHand' => [],
                'cards_left' => 0
            ];
    
            /**
             * @param array{playerHand: array<string, array<int, array<string, string>>>, cards_left: int} $parameters
             */
            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->params = $parameters;
                $this->usedTemplate = $view;
                return new Response("rendered");
            }
        };
    
        $response = $controller->drawCardsPlayers(2, 3, $session);
    
        $this->assertEquals("rendered", $response->getContent());
        $this->assertEquals("card/deal.html.twig", $controller->usedTemplate);
        $this->assertArrayHasKey("Player 1", $controller->params["playerHand"]);
        $this->assertCount(2, $controller->params["playerHand"]["Player 1"]);
        $this->assertEquals(4, $controller->params["cards_left"]);
    }

    public function testDrawCardsPlayersThrowsIfNotEnoughCards(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set("shuffled_deck", array_fill(0, 4, ["value" => "5", "suit" => "hearts"]));

        $controller = new CardController();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Cannot draw more cards than are left in the deck!");

        $controller->drawCardsPlayers(3, 2, $session);
    }

    public function testDrawCardsPlayersThrowsIfDeckNotArray(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set("shuffled_deck", "not-an-array");

        $controller = new CardController();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("No valid deck found in session");

        $controller->drawCardsPlayers(1, 1, $session);
    }
}
