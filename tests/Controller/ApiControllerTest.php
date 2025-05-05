<?php

namespace App\Controller;

use App\Controller\ApiController;
use App\Card\DeckOfCards;
use PHPUnit\Framework\TestCase;
use LogicException;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;
use Exception;

class ApiControllerTest extends TestCase
{
    private function invokeAssertArray($value, $name): array
    {
        $controller = new ApiController();
        $method = new ReflectionMethod(ApiController::class, 'assertArray');
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

    public function testApiDeckReturns52Cards(): void
    {
        $controller = new ApiController();
        $response = $controller->apiDeck();

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);

        $this->assertCount(52, $data);
        $this->assertArrayHasKey('suit', $data[0]);
        $this->assertArrayHasKey('value', $data[0]);
    }

    public function testApiShuffle(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $controller = new ApiController();
        $response = $controller->deckShuffleApi($session);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);

        $this->assertCount(52, $data);
        $this->assertArrayHasKey('suit', $data[0]);
        $this->assertArrayHasKey('value', $data[0]);

        $this->assertTrue($session->has('shuffled_deck'));
        $this->assertCount(52, $session->get('shuffled_deck'));
    }

    public function testDrawCardApi(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $deck = new DeckOfCards();
        $deck->shuffle();

        $cards = array_map(fn ($card) => [
            'suit' => $card->getSuit(),
            'value' => $card->getValue()
        ], $deck->getDeck());

        $session->set("shuffled_deck", $cards);

        $controller = new ApiController();
        $response = $controller->drawCardApi($session);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey("drawn", $data);
        $this->assertArrayHasKey("cards_left", $data);
        $this->assertCount(1, $data["drawn"]);
        $this->assertEquals(51, $data["cards_left"]);
    }

    public function testDrawMultipleCards(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $deck = new DeckOfCards();
        $deck->shuffle();

        $cards = array_map(fn ($card) => [
            'suit' => $card->getSuit(),
            'value' => $card->getValue()
        ], $deck->getDeck());

        $session->set("shuffled_deck", $cards);

        $controller = new ApiController();
        $response = $controller->drawCardsApi(5, $session);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);

        $this->assertCount(5, $data["drawn_cards"]);
        $this->assertEquals(47, $data["cards_left"]);
    }

    public function testDrawTooManyCardsThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Cannot draw more cards than are left in the deck!");

        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $session->set("shuffled_deck", []);

        $controller = new ApiController();
        $controller->drawCardsApi(1, $session);
    }

    public function testDealCardsToPlayers(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $deck = new DeckOfCards();
        $deck->shuffle();

        $cards = array_map(fn ($card) => [
            'suit' => $card->getSuit(),
            'value' => $card->getValue()
        ], $deck->getDeck());

        $session->set("shuffled_deck", $cards);

        $controller = new ApiController();
        $response = $controller->drawCardsPlayersApi(2, 3, $session);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);

        $this->assertCount(3, $data["playerHand"]);
        $this->assertCount(2, $data["playerHand"]["Player 1"]);
        $this->assertEquals(52 - 6, $data["cards_left"]);
    }

    public function testDealTooManyCardsThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Cannot draw more cards than are left in the deck!");

        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $deck = new DeckOfCards();
        $deck->shuffle();

        $cards = array_map(fn ($card) => [
            'suit' => $card->getSuit(),
            'value' => $card->getValue()
        ], $deck->getDeck());

        $session->set("shuffled_deck", $cards);

        $controller = new ApiController();
        $controller->drawCardsPlayersApi(30, 2, $session);
    }

    public function testGameApiReturnsExpectedSessionData(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $session->set("coins", 100);
        $session->set("player_cards", [["value" => "A", "suit" => "Spades"]]);
        $session->set("dealer_cards", [["value" => "10", "suit" => "Hearts"]]);
        $session->set("player_points", 21);
        $session->set("dealer_points", 18);

        $controller = new ApiController();
        $response = $controller->gameApi($session);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(100, $data["coins"]);
        $this->assertEquals([["value" => "A", "suit" => "Spades"]], $data["player cards"]);
        $this->assertEquals([["value" => "10", "suit" => "Hearts"]], $data["dealer cards"]);
        $this->assertEquals(21, $data["player points"]);
        $this->assertEquals(18, $data["dealer points"]);
    }
}
