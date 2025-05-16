<?php

namespace App\Controller;

// use App\Card\CardGrafic;
use App\Card\DeckOfCards;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Exception;
use LogicException;

class CardController extends AbstractController
{
    #[Route("/card", name: "card_start")]
    public function home(
    ): Response {
        // $session->set("card_message", "Välkommen till kortspelet!");
        return $this->render('card/home.html.twig');
    }

    #[Route("/session", name: "session")]
    public function session(
        SessionInterface $session
    ): Response {
        $allSession = $session->all();

        return $this->render('card/session.html.twig', [
            'session' => $allSession
        ]);
    }

    #[Route("/session/delete", name: "session_delete")]
    public function sessionDelete(
        SessionInterface $session
    ): Response {
        $session->clear();
        $this->addFlash(
            'notice',
            'Session is now cleard!'
        );
        return $this->redirectToRoute('session');
    }

    #[Route("/game/card/deck", name: "card_deck")]
    public function cardDeck(): Response
    {
        $deck = new DeckOfCards();
        $cards = $deck->getDeck();

        return $this->render("card/deck.html.twig", [
            "cards" => $cards
        ]);
    }

    #[Route("/game/card/shuffle", name: "deck_shuffle")]
    public function deckShuffle(
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

        return $this->render("card/shuffle.html.twig", [
            "cards" => $cards
        ]);
    }

    #[Route("card/deck/draw", name: "draw_one_card")]
    public function drawCard(
        SessionInterface $session
    ): Response {
        $shuffledDeck = $session->get("shuffled_deck", []);

        if (!is_array($shuffledDeck)) {
            throw new LogicException('No valid deck found in session');
        }

        $drawnCards = array_splice($shuffledDeck, 0, 1);
        $session->set("shuffled_deck", $shuffledDeck);

        return $this->render("card/draw.html.twig", [
            "drawn_cards" => $drawnCards,
            "cards_left" => count($shuffledDeck),
        ]);
    }

    #[Route("card/deck/draw/{num<\d+>}", name: "draw_n_cards")]
    public function drawCards(
        int $num,
        SessionInterface $session
    ): Response {
        $shuffledDeck = $session->get("shuffled_deck", []);
        if (!is_array($shuffledDeck)) {
            throw new LogicException('No valid deck found in session');
        }
        $totalCards = count($shuffledDeck);

        if ($num > $totalCards) {
            throw new Exception("Cannot draw more cards than are left in the deck!");
        }

        $drawnCards = array_splice($shuffledDeck, 0, $num);
        $session->set("shuffled_deck", $shuffledDeck);

        return $this->render("card/draw.html.twig", [
            "drawn_cards" => $drawnCards,
            "cards_left" => count($shuffledDeck),
        ]);
    }

    #[Route("card/deck/deal/{play<\d+>}/{num<\d+>}", name: "draw_n_cards_x_players")]
    public function drawCardsPlayers(
        int $num,
        int $play,
        SessionInterface $session
    ): Response {
        $shuffledDeck = $session->get("shuffled_deck", []);

        if (!is_array($shuffledDeck)) {
            throw new LogicException('No valid deck found in session');
        }
        $totalCards = count($shuffledDeck);

        if ($num * $play > $totalCards) {
            throw new Exception("Cannot draw more cards than are left in the deck!");
        }

        $playerHand = [];

        for ($i = 1; $i <= $play; $i++) {
            $playerHand["Player $i"] = array_splice($shuffledDeck, 0, $num);
        }

        $session->set("shuffled_deck", $shuffledDeck);

        return $this->render("card/deal.html.twig", [
            "playerHand" => $playerHand,
            "cards_left" => count($shuffledDeck),
        ]);
    }
}
