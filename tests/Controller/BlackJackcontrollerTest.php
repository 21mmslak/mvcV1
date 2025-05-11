<?php
// @phpstan-ignore-file

namespace App\Controller;

use PHPUnit\Framework\TestCase;
use App\Controller\BlackJackController;
use App\BlackJack\BlackJackRules;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use ReflectionProperty;
use LogicException;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class BlackJackControllerTest extends TestCase
{
    public function testConstructorInitializesRules(): void
    {
        $controller = new BlackJackController();

        $property = new ReflectionProperty(BlackJackController::class, 'rules');
        $property->setAccessible(true);
        $rules = $property->getValue($controller);

        $this->assertInstanceOf(BlackJackRules::class, $rules);
    }

    private function invokeAssertArray($value, $name): array
    {
        $controller = new BlackJackController();
        $method = new ReflectionMethod(BlackJackController::class, 'assertArray');
        $method->setAccessible(true);

        return $method->invoke($controller, $value, $name);
    }

    public function testAssertArrayReturnsArray(): void
    {
        $value = ['a' => 1, 'b' => 2];
        $result = $this->invokeAssertArray($value, "test_key");

        $this->assertSame($value, $result);
    }

    public function testAssertArrayThrowsExceptionForNonArray(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Expected array for invalid_key in session");

        $this->invokeAssertArray("not an array", "invalid_key");
    }

    private function invokeAssertInt($value, $name): int
    {
        $controller = new BlackJackController();
        $method = new ReflectionMethod(BlackJackController::class, 'assertInt');
        $method->setAccessible(true);

        return $method->invoke($controller, $value, $name);
    }

    public function testAssertIntReturnsInt(): void
    {
        $result = $this->invokeAssertInt(42, "coins");
        $this->assertSame(42, $result);
    }

    public function testAssertIntThrowsExceptionForNonInt(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Expected int for not_int_key in session");

        $this->invokeAssertInt("not an int", "not_int_key");
    }

    public function testDocReturnsResponse(): void
    {
        $controller = new class () extends BlackJackController {
            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                return new Response("Rendering {$view}");
            }
        };

        $response = $controller->doc();
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testRouleReturnsResponse(): void
    {
        $controller = new class () extends BlackJackController {
            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                return new Response("Rendering {$view}");
            }
        };

        $response = $controller->roule();
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testGameStartRendersCorrectTemplate(): void
    {
        $session = new Session(new MockArraySessionStorage());

        $controller = new class () extends BlackJackController {
            public string $usedTemplate = '';
            public array $renderParams = [];

            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->usedTemplate = $view;
                $this->renderParams = $parameters;
                return new Response("mocked");
            }
        };

        $response = $controller->gameStart($session);

        $this->assertEquals("mocked", $response->getContent());
        $this->assertContains($controller->usedTemplate, [
            "black_jack/winner.html.twig",
            "black_jack/game_start.html.twig"
        ]);

        $this->assertTrue($session->has("player_cards"));
        $this->assertTrue($session->has("dealer_cards"));
        $this->assertTrue($session->has("shuffled_deck"));
        $this->assertTrue($session->has("player_points"));
        $this->assertTrue($session->has("dealer_points"));
        $this->assertTrue($session->has("coins"));
    }

    public function testGameStartReturnsWinnerViewIfPlayerOver21(): void
    {
        $session = new Session(new MockArraySessionStorage());

        $controller = new class () extends BlackJackController {
            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                return new Response("winner");
            }
        };

        $rulesProp = new ReflectionProperty(BlackJackController::class, 'rules');
        $rulesProp->setAccessible(true);
        $rulesProp->setValue($controller, new class () extends BlackJackRules {
            public function countPoints(array $hand): int
            {
                return 22;
            }

            public function checkOver(int $points): bool
            {
                return true;
            }

            public function decideWinner(int $dealer, int $player, $session): string
            {
                return "Player Win!";
            }
        });

        $response = $controller->gameStart($session);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('winner', $response->getContent());
    }

    public function testSplitIsTrueWhenCardsMatch(): void
    {
        $playerStart = [
            ['value' => '8', 'suit' => 'hearts'],
            ['value' => '8', 'suit' => 'clubs']
        ];

        $split = false;

        if ($playerStart[0]['value'] === $playerStart[1]['value']) {
            $split = true;
        }

        $this->assertTrue($split);
    }

    public function testSplitIsFalseWhenCardsDiffer(): void
    {
        $playerStart = [
            ['value' => '7', 'suit' => 'hearts'],
            ['value' => '9', 'suit' => 'clubs']
        ];

        $split = false;

        if ($playerStart[0]['value'] === $playerStart[1]['value']) {
            $split = true;
        }

        $this->assertFalse($split);
    }

    public function testAddCardAddsCardAndRendersGameStart(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set("shuffled_deck", [
            ["value" => "5", "suit" => "clubs"]
        ]);
        $session->set("player_cards", []);
        $session->set("dealer_cards", []);
        $session->set("player_points", 10);
        $session->set("dealer_points", 10);
        $session->set("is_split", false);

        $controller = new class () extends BlackJackController {
            public string $usedTemplate = '';
            public array $params = [];

            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->usedTemplate = $view;
                $this->params = $parameters;
                return new Response("mocked");
            }
        };

        $response = $controller->addCard($session);

        $this->assertEquals("mocked", $response->getContent());
        $this->assertEquals("black_jack/game_start.html.twig", $controller->usedTemplate);
        $this->assertArrayHasKey("player", $controller->params);
        $this->assertCount(1, $controller->params["player"]);
    }

    public function testAddCardReturnsWinnerViewWhenOver(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set("shuffled_deck", [["value" => "10", "suit" => "hearts"]]);
        $session->set("player_cards", []);
        $session->set("dealer_cards", []);
        $session->set("player_points", 22);
        $session->set("dealer_points", 17);
        $session->set("coins", 100);

        $mockRules = new class () extends BlackJackRules {
            public function checkOver(int $points): bool
            {
                return true;
            }
            public function decideWinner(int $dealer, int $player, $session): string
            {
                return "Mocked Winner";
            }
        };

        $controller = new class ($mockRules) extends BlackJackController {
            public string $usedTemplate = '';
            public array $params = [];

            public function __construct(private BlackJackRules $mockedRules)
            {
                parent::__construct();
                $this->rules = $mockedRules;
            }

            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->usedTemplate = $view;
                $this->params = $parameters;
                return new Response("winner render");
            }
        };

        $response = $controller->addCard($session);

        $this->assertEquals("winner render", $response->getContent());
        $this->assertEquals("black_jack/winner.html.twig", $controller->usedTemplate);
        $this->assertEquals("Mocked Winner", $controller->params["winner"]);
    }

    public function testAddCardSplitHand1(): void
    {
        $session = new Session(new MockArraySessionStorage());

        $session->set("shuffled_deck", [["value" => "10", "suit" => "spades"]]);
        $session->set("active_hand", "hand1");
        $session->set("hand1", []);
        $session->set("hand2", []);
        $session->set("dealer_cards", []);
        $session->set("dealer_points", 10);
        $session->set("coins", 100);

        $mockRules = new class () extends BlackJackRules {
            public function countPoints(array $hand): int
            {
                return 15;
            }
        };

        $controller = new class ($mockRules) extends BlackJackController {
            public string $usedTemplate = '';
            public array $params = [];

            public function __construct(private BlackJackRules $mockedRules)
            {
                parent::__construct();
                $this->rules = $mockedRules;
            }

            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->usedTemplate = $view;
                $this->params = $parameters;
                return new Response("split render");
            }
        };

        $response = $controller->addCardSplit($session);

        $this->assertEquals("split render", $response->getContent());
        $this->assertEquals("black_jack/game_split.html.twig", $controller->usedTemplate);
        $this->assertTrue($session->has("hand1"));
        $this->assertSame(15, $session->get("player_points_1"));
    }

    public function testAddCardSplitRedirectsWhenHand2PointsHigh(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->replace([
            "shuffled_deck" => [["value" => "10", "suit" => "spades"]],
            "active_hand" => "hand2",
            "hand1" => [],
            "hand2" => [],
            "dealer_cards" => [],
            "dealer_points" => 10
        ]);

        $mockRules = new class () extends BlackJackRules {
            public function countPoints(array $hand): int
            {
                return 21;
            }
        };

        $controller = new class ($mockRules) extends BlackJackController {
            public string $redirectedTo = '';
            public function __construct(public BlackJackRules $mockedRules)
            {
                $this->rules = $mockedRules;
            }
            public function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
            {
                $this->redirectedTo = $route;
                return new RedirectResponse("/mock/$route", $status);
            }
        };

        $response = $controller->addCardSplit($session);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame("stand_split", $controller->redirectedTo);
        $this->assertSame(21, $session->get("player_points_2"));
    }

    public function testAddCardSplitSwitchesToHand2WhenHand1Over21(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->replace([
            "shuffled_deck" => [["value" => "10", "suit" => "hearts"]],
            "active_hand" => "hand1",
            "hand1" => [],
            "hand2" => [],
            "dealer_cards" => [],
            "dealer_points" => 15
        ]);

        $mockRules = new class () extends BlackJackRules {
            public function countPoints(array $hand): int
            {
                return 21;
            }
        };

        $controller = new class ($mockRules) extends BlackJackController {
            public function __construct(public BlackJackRules $mockedRules)
            {
                $this->rules = $mockedRules;
            }

            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                return new Response("split continue");
            }
        };

        $controller->addCardSplit($session);

        $this->assertSame("hand2", $session->get("active_hand"));
    }

    public function testStandDealerDrawsUnder16(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set("shuffled_deck", [
            ["value" => "10", "suit" => "hearts"]
        ]);
        $session->set("dealer_cards", [["value" => "2", "suit" => "spades"]]);
        $session->set("player_points", 18);
        $session->set("player_cards", [["value" => "9", "suit" => "clubs"]]);
        $session->set("coins", 100);

        $mockRules = new class () extends BlackJackRules {
            public int $calls = 0;
            public function countPoints(array $hand): int
            {
                return ++$this->calls === 1 ? 10 : 16;
            }
            public function decideWinner(int $dealer, int $player, $session): string
            {
                return "Player Win!";
            }
        };

        $controller = new class ($mockRules) extends BlackJackController {
            public string $template = "";
            public array $params = [];

            public function __construct(public $mockedRules)
            {
                $this->rules = $mockedRules;
            }

            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->template = $view;
                $this->params = $parameters;
                return new Response("winner render");
            }
        };

        $response = $controller->stand($session);

        $this->assertEquals("winner render", $response->getContent());
        $this->assertEquals("black_jack/winner.html.twig", $controller->template);
        $this->assertSame("Player Win!", $controller->params['winner']);
        $this->assertSame(16, $controller->params['dealerPoints']);
    }

    public function testStandNoDrawNeeded(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set("shuffled_deck", []);
        $session->set("dealer_cards", [["value" => "10", "suit" => "hearts"]]);
        $session->set("player_points", 18);
        $session->set("player_cards", [["value" => "9", "suit" => "clubs"]]);
        $session->set("coins", 100);

        $mockRules = new class () extends BlackJackRules {
            public function countPoints(array $hand): int
            {
                return 17;
            }
            public function decideWinner(int $dealer, int $player, $session): string
            {
                return "Draw!";
            }
        };

        $controller = new class ($mockRules) extends BlackJackController {
            public string $template = "";
            public array $params = [];

            public function __construct(public $mockedRules)
            {
                $this->rules = $mockedRules;
            }

            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->template = $view;
                $this->params = $parameters;
                return new Response("winner render");
            }
        };

        $response = $controller->stand($session);

        $this->assertEquals("winner render", $response->getContent());
        $this->assertSame("Draw!", $controller->params['winner']);
        $this->assertSame(17, $controller->params['dealerPoints']);
    }

    public function testStandSplitSwitchesToHand2(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set("active_hand", "hand1");
        $session->set("dealer_cards", []);
        $session->set("dealer_points", 0);
        $session->set("hand1", []);
        $session->set("hand2", []);
        $session->set("player_points_1", 0);
        $session->set("player_points_2", 0);
        $session->set("coins", 100);

        $controller = new class () extends BlackJackController {
            public string $templateUsed = '';
            public array $params = [];
            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->templateUsed = $view;
                $this->params = $parameters;
                return new Response("rendered");
            }
        };

        $response = $controller->standSplit($session);

        $this->assertEquals("rendered", $response->getContent());
        $this->assertEquals("black_jack/game_split.html.twig", $controller->templateUsed);
        $this->assertSame("hand2", $controller->params['active']);
        $this->assertSame("hand2", $session->get("active_hand"));
    }

    public function testStandSplitEvaluatesBothHands(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->replace([
            "active_hand" => "hand2",
            "shuffled_deck" => [["value" => "5", "suit" => "clubs"]],
            "dealer_cards" => [["value" => "6", "suit" => "hearts"]],
            "player_points_1" => 18,
            "player_points_2" => 20,
            "hand1" => [],
            "hand2" => [],
            "dealer_points" => 6,
            "coins" => 100,
        ]);

        $mockRules = new class () extends BlackJackRules {
            public function countPoints(array $hand): int
            {
                return 17;
            }
            public function decideWinner(int $dealer, int $player, $session): string
            {
                return $player > $dealer ? "Player Wins" : "Dealer Wins";
            }
        };

        $controller = new class ($mockRules) extends BlackJackController {
            public string $templateUsed = '';
            public array $params = [];
            public function __construct(public BlackJackRules $mockedRules)
            {
                $this->rules = $mockedRules;
            }
            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->templateUsed = $view;
                $this->params = $parameters;
                return new Response("winner split rendered");
            }
        };

        $response = $controller->standSplit($session);

        $this->assertEquals("winner split rendered", $response->getContent());
        $this->assertEquals("black_jack/winner_split.html.twig", $controller->templateUsed);
        $this->assertEquals("Player Wins", $controller->params['winner1']);
        $this->assertEquals("Player Wins", $controller->params['winner2']);
    }

    public function testStandSplitDealerDrawsCardsUntilSixteen(): void
    {
        $session = new Session(new MockArraySessionStorage());

        $session->replace([
            "active_hand" => "hand2",
            "shuffled_deck" => [
                ["value" => "5", "suit" => "hearts"],
            ],
            "dealer_cards" => [],
            "player_points_1" => 10,
            "player_points_2" => 15,
            "hand1" => [],
            "hand2" => [],
            "coins" => 100
        ]);

        $mockRules = new class () extends BlackJackRules {
            private int $calls = 0;
            public function countPoints(array $hand): int
            {
                return ++$this->calls === 1 ? 10 : 16;
            }
            public function decideWinner(int $dealer, int $player, $session): string
            {
                return "Mock Winner";
            }
        };

        $controller = new class ($mockRules) extends BlackJackController {
            public string $template = '';
            public array $params = [];

            public function __construct(private BlackJackRules $mockedRules)
            {
                $this->rules = $mockedRules;
            }

            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->template = $view;
                $this->params = $parameters;
                return new Response("stand split loop");
            }
        };

        $response = $controller->standSplit($session);

        $this->assertEquals("stand split loop", $response->getContent());
        $this->assertEquals("black_jack/winner_split.html.twig", $controller->template);
        $this->assertCount(1, $controller->params['dealer']);
        $this->assertEquals("Mock Winner", $controller->params['winner1']);
        $this->assertEquals("Mock Winner", $controller->params['winner2']);
    }

    public function testSplitSeparatesHandsCorrectly(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set("player_cards", [
            ["value" => "8", "suit" => "hearts"],
            ["value" => "8", "suit" => "clubs"]
        ]);
        $session->set("shuffled_deck", [
            ["value" => "5", "suit" => "spades"],
            ["value" => "2", "suit" => "diamonds"]
        ]);
        $session->set("dealer_cards", []);
        $session->set("dealer_points", 0);

        $mockRules = new class () extends BlackJackRules {
            public function countPoints(array $hand): int
            {
                return array_sum(array_map(fn ($c) => (int)$c["value"], $hand));
            }
        };

        $controller = new class ($mockRules) extends BlackJackController {
            public string $usedTemplate = '';
            public array $params = [];

            public function __construct(private BlackJackRules $mockedRules)
            {
                $this->rules = $mockedRules;
            }

            public function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->usedTemplate = $view;
                $this->params = $parameters;
                return new Response("split render");
            }
        };

        $response = $controller->split($session);

        $this->assertEquals("split render", $response->getContent());
        $this->assertEquals("black_jack/game_split.html.twig", $controller->usedTemplate);

        $this->assertSame([["value" => "8", "suit" => "hearts"], ["value" => "5", "suit" => "spades"]], $controller->params['hand1']);
        $this->assertSame([["value" => "8", "suit" => "clubs"], ["value" => "2", "suit" => "diamonds"]], $controller->params['hand2']);
        $this->assertEquals(13, $controller->params['totplayer1']);
        $this->assertEquals(10, $controller->params['totplayer2']);
        $this->assertTrue($session->get("is_split"));
    }
}
