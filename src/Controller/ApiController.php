<?php

namespace App\Controller;

use App\Card\DeckOfCards;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ApiController extends AbstractController
{
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
        $shuffledDeck = $session->get("shuffled_deck", []);

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
        $shuffledDeck = $session->get("shuffled_deck", []);
        $totalCards = count($shuffledDeck);

        if ($num > $totalCards) {
            throw new \Exception("Cannot draw more cards than are left in the deck!");
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
        $shuffledDeck = $session->get("shuffled_deck", []);
        $totalCards = count($shuffledDeck);

        if ($num * $play > $totalCards) {
            throw new \Exception("Cannot draw more cards than are left in the deck!");
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
        $player_cards = $session->get("player_cards", []);
        $dealer_cards = $session->get("dealer_cards", []);
        $player_points = $session->get("player_points", []);
        $dealer_points = $session->get("dealer_points", []);

        return new JsonResponse([
            "coins" => $coins,
            "player cards" => $player_cards,
            "dealer cards" => $dealer_cards,
            "player points" => $player_points,
            "dealer points" => $dealer_points,
        ]);
    }
}
