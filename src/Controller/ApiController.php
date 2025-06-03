<?php

namespace App\Controller;

use App\Card\DeckOfCards;
use App\Repository\BooksRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use LogicException;
use Exception;

class ApiController extends AbstractController
{
    /**
     * Assert that a value is an array.
     * @return array<int|string, mixed>
     */
    private function assertArray(mixed $value, string $name): array
    {
        if (!is_array($value)) {
            throw new LogicException("Expected array for {$name} in session, got something else.");
        }
        return $value;
    }

    #[Route("/api/deck", name: "apiDeck", methods: ["GET"])]
    public function apiDeck(): Response
    {
        $deck = new DeckOfCards();

        $cards = array_map(function ($card) {
            return [
                'suit' => $card->getSuit(),
                'value' => $card->getValue()
            ];
        }, $deck->getDeck());

        return new JsonResponse($cards);
    }

    #[Route("/api/deck/shuffle", name: "deck_shuffle_api", methods:["POST"])]
    public function deckShuffleApi(
        SessionInterface $session
    ): Response {
        $deck = new DeckOfCards();
        $deck->shuffle();

        $cards = $deck->getDeck();

        $cardData = array_map(function ($card) {
            return [
                "suit" => $card->getSuit(),
                "value" => $card->getValue(),
            ];
        }, $cards);

        $session->set("shuffled_deck", $cardData);

        return new JsonResponse($cardData);
    }

    #[Route("api/deck/draw", name: "draw_one_card_api", methods: ["POST"])]
    public function drawCardApi(
        SessionInterface $session
    ): Response {
        $shuffledDeck = $this->assertArray($session->get("shuffled_deck", []), "shuffled_deck");

        $drawnCards = array_splice($shuffledDeck, 0, 1);
        $session->set("shuffled_deck", $shuffledDeck);

        return new JsonResponse([
            "drawn" => $drawnCards,
            "cards_left" => count($shuffledDeck)]);
    }

    #[Route("api/deck/draw/{num<\d+>}", name: "draw_n_cards_api", methods: ["POST", "GET"])]
    public function drawCardsApi(
        int $num,
        SessionInterface $session
    ): Response {
        $shuffledDeck = $this->assertArray($session->get("shuffled_deck", []), "shuffled_deck");
        $totalCards = count($shuffledDeck);

        if ($num > $totalCards) {
            throw new Exception("Cannot draw more cards than are left in the deck!");
        }

        $drawnCards = array_splice($shuffledDeck, 0, $num);
        $session->set("shuffled_deck", $shuffledDeck);

        return new JsonResponse([
            "drawn_cards" => $drawnCards,
            "cards_left" => count($shuffledDeck),
        ]);
    }

    #[Route("api/deck/deal/{play<\d+>}/{num<\d+>}", name: "draw_n_cards_x_players_api")]
    public function drawCardsPlayersApi(
        int $num,
        int $play,
        SessionInterface $session
    ): Response {
        $shuffledDeck = $this->assertArray($session->get("shuffled_deck", []), "shuffled_deck");
        $totalCards = count($shuffledDeck);

        if ($num * $play > $totalCards) {
            throw new Exception("Cannot draw more cards than are left in the deck!");
        }

        $playerHand = [];

        for ($i = 1; $i <= $play; $i++) {
            $playerHand["Player $i"] = array_splice($shuffledDeck, 0, $num);
        }

        $session->set("shuffled_deck", $shuffledDeck);

        return new JsonResponse([
            "playerHand" => $playerHand,
            "cards_left" => count($shuffledDeck),
        ]);
    }

    #[Route("api/game", name: "game_api")]
    public function gameApi(
        SessionInterface $session
    ): Response {
        $coins = $session->get("coins", []);
        $playerCards = $session->get("player_cards", []);
        $dealerCards = $session->get("dealer_cards", []);
        $playerPoints = $session->get("player_points", []);
        $dealerPoints = $session->get("dealer_points", []);

        return new JsonResponse([
            "coins" => $coins,
            "player cards" => $playerCards,
            "dealer cards" => $dealerCards,
            "player points" => $playerPoints,
            "dealer points" => $dealerPoints,
        ]);
    }

    #[Route("api/library/books", name: "books_api")]
    public function booksApi(
        BooksRepository $booksRepository
    ): Response {
        $books = $booksRepository->findAll();

        $data = array_map(function ($book) {
            return [
                'id' => $book->getId(),
                'titel' => $book->getTitel(),
                'ISBN' => $book->getISBN(),
                'författare' => $book->getFörfattare(),
                'bild' => $book->getBild()
            ];
        }, $books);

        return new JsonResponse(['books' => $data]);
    }

    #[Route("api/library/book/{isbn<\d+>}", name: "book_by_isbn_api")]
    public function bookIsbnApi(
        BooksRepository $booksRepository,
        string $isbn
    ): Response {
        $book = $booksRepository->findOneBy(['ISBN' => $isbn]);
    
        if (!$book) {
            return new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }
    
        $data = [
            'id' => $book->getId(),
            'titel' => $book->getTitel(),
            'ISBN' => $book->getISBN(),
            'författare' => $book->getFörfattare(),
            'bild' => $book->getBild()
        ];
    
        return new JsonResponse($data);
    }
}
