<?php

namespace App\Controller;

use App\Card\DeckOfCards;
use App\BlackJack\BlackJack;
use App\BlackJack\BlackJackRules;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Controller\CardController;
use LogicException;

use function PHPUnit\Framework\throwException;

class BlackJackController extends AbstractController
{
    protected BlackJackRules $rules;

    public function __construct()
    {
        $this->rules = new BlackJackRules();
    }
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

    private function assertInt(mixed $value, string $name): int
    {
        if (!is_int($value)) {
            throw new LogicException("Expected int for {$name} in session, got something else.");
        }
        return $value;
    }

    #[Route('/doc', name: 'doc')]
    public function doc(): Response
    {
        return $this->render('black_jack/doc.html.twig');
    }

    #[Route('/roule', name: 'roule')]
    public function roule(): Response
    {
        return $this->render('black_jack/roule.html.twig');
    }

    #[Route('/game_start', name: 'game_start')]
    public function gameStart(SessionInterface $session): Response
    {
        $blackJack = new BlackJack();
        $cards = $blackJack->startGame($session);
        if (!$session->has('coins')) {
            $session->set('coins', 100);
        }

        $playerStart = array_splice($cards, 0, 2);
        $dealerStart = array_splice($cards, 0, 2);

        $playerPoints = $this->rules->countPoints($playerStart);
        $dealerPoints = $this->rules->countPoints($dealerStart);

        $session->set("player_cards", $playerStart);
        $session->set("dealer_cards", $dealerStart);
        $session->set("shuffled_deck", $cards);
        $session->set("player_points", $playerPoints);
        $session->set("dealer_points", $dealerPoints);

        $over = $this->rules->checkOver($playerPoints);
        if ($over) {
            $winner = $this->rules->decideWinner($dealerPoints, $playerPoints, $session);

            return $this->render("black_jack/winner.html.twig", [
                "winner" => $winner,
                "dealer" => $session->get("dealer_cards", []),
                "player" => $session->get("player_cards", []),
                "dealerPoints" => $session->get("dealer_points", []),
                "playerPoints" => $session->get("player_points", []),
                "coins" => $session->get("coins", 100)
            ]);
        }

        $split = false;
        $session->set("is_split", false);
        if ($playerStart[0]['value'] === $playerStart[1]['value']) {
            $split = true;
        }

        return $this->render("black_jack/game_start.html.twig", [
            "player" => $playerStart,
            "dealer" => $dealerStart,
            "dealerPoints" => $dealerPoints,
            "playerPoints" => $playerPoints,
            "split" => $split,
            "splittat" => false,
            "coins" => $session->get("coins", 100)
        ]);
    }

    #[Route('/add_card', name: 'add_card')]
    public function addCard(SessionInterface $session): Response
    {
        $cards = $this->assertArray($session->get("shuffled_deck"), "shuffled_deck");

        $newCard = array_shift($cards);

        $playerCards = $this->assertArray($session->get("player_cards"), "player_cards");
        $dealerCards = $this->assertArray($session->get("dealer_cards"), "dealer_cards");
        $dealerPoints = $this->assertInt($session->get("dealer_points"), "dealer_points");
        $playerPoints = $this->assertInt($session->get("player_points"), "player_points");

        if ($playerPoints < 21) {
            $playerCards[] = $newCard;
        }

        $playerPoints = $this->rules->countPoints($playerCards);

        $session->set("shuffled_deck", $cards);
        $session->set("player_cards", $playerCards);
        $session->set("player_points", $playerPoints);

        $over = $this->rules->checkOver($playerPoints);
        if ($over) {
            $winner = $this->rules->decideWinner($dealerPoints, $playerPoints, $session);

            return $this->render("black_jack/winner.html.twig", [
                "winner" => $winner,
                "dealer" => $session->get("dealer_cards", []),
                "player" => $session->get("player_cards", []),
                "dealerPoints" => $session->get("dealer_points", []),
                "playerPoints" => $session->get("player_points", []),
                "coins" => $session->get("coins", 100)
            ]);
        }

        return $this->render("black_jack/game_start.html.twig", [
            "player" => $playerCards,
            "dealer" => $dealerCards,
            "dealerPoints" => $dealerPoints,
            "playerPoints" => $playerPoints,
            "coins" => $session->get("coins", 100),
            "split" => $session->get("is_split")
        ]);
    }




    #[Route('/add_card_split', name: 'add_card_split')]
    public function addCardSplit(SessionInterface $session): Response
    {
        $cards = $this->assertArray($session->get("shuffled_deck"), "shuffled_deck");
        $activeHand = $session->get("active_hand", "hand1");

        $newCard = array_shift($cards);

        $hand1 = $this->assertArray($session->get("hand1"), "hand1");
        $hand2 = $this->assertArray($session->get("hand2"), "hand2");
        $dealerCards = $this->assertArray($session->get("dealer_cards"), "dealer_cards");
        $dealerPoints = $this->assertInt($session->get("dealer_points"), "dealer_points");

        if ($activeHand === "hand1") {
            $hand1[] = $newCard;
            $playerPoints1 = $this->rules->countPoints($hand1);
            $session->set("hand1", $hand1);
            $session->set("player_points_1", $playerPoints1);

            if ($playerPoints1 >= 21) {
                $session->set("active_hand", "hand2");
            }

        } elseif ($activeHand === "hand2") {
            $hand2[] = $newCard;
            $playerPoints2 = $this->rules->countPoints($hand2);
            $session->set("hand2", $hand2);
            $session->set("player_points_2", $playerPoints2);

            if ($playerPoints2 >= 20) {
                return $this->redirectToRoute("stand_split");
            }
        }

        $session->set("shuffled_deck", $cards);

        return $this->render("black_jack/game_split.html.twig", [
            "dealer" => $dealerCards,
            "dealerPoints" => $dealerPoints,
            "hand1" => $hand1,
            "hand2" => $hand2,
            "totplayer1" => $session->get("player_points_1", 0),
            "totplayer2" => $session->get("player_points_2", 0),
            "splittat" => true,
            "split" => false,
            "coins" => $session->get("coins", 100)
        ]);
    }





    #[Route('/stand', name: 'stand')]
    public function stand(SessionInterface $session): Response
    {
        $cards = $this->assertArray($session->get("shuffled_deck"), "shuffled_deck");
        $dealerCards = $this->assertArray($session->get("dealer_cards"), "dealer_cards");
        $playerPoints = $this->assertInt($session->get("player_points"), "player_points");

        $dealerPoints = $this->rules->countPoints($dealerCards);
        while ($dealerPoints < 16) {
            $newCard = array_shift($cards);
            $dealerCards[] = $newCard;
            $dealerPoints = $this->rules->countPoints($dealerCards);
        }

        $session->set("dealer_cards", $dealerCards);
        $session->set("dealer_points", $dealerPoints);
        $session->set("shuffled_deck", $cards);

        $winner = $this->rules->decideWinner($dealerPoints, $playerPoints, $session);

        return $this->render("black_jack/winner.html.twig", [
            "winner" => $winner,
            "dealer" => $dealerCards,
            "player" => $session->get("player_cards", []),
            "dealerPoints" => $dealerPoints,
            "playerPoints" => $playerPoints,
            "coins" => $session->get("coins", 100)
        ]);
    }




    #[Route('/stand_split', name: 'stand_split')]
    public function standSplit(SessionInterface $session): Response
    {
        $activeHand = $session->get("active_hand", "hand1");

        if ($activeHand === "hand1") {
            $session->set("active_hand", "hand2");

            return $this->render("black_jack/game_split.html.twig", [
                "dealer" => $session->get("dealer_cards", []),
                "dealerPoints" => $session->get("dealer_points", []),
                "hand1" => $session->get("hand1", []),
                "hand2" => $session->get("hand2", []),
                "totplayer1" => $session->get("player_points_1", 0),
                "totplayer2" => $session->get("player_points_2", 0),
                "splittat" => true,
                "split" => false,
                "coins" => $session->get("coins", 100),
                "active" => "hand2"
            ]);
        }

        $cards = $this->assertArray($session->get("shuffled_deck"), "shuffled_deck");
        $dealerCards = $this->assertArray($session->get("dealer_cards"), "dealer_cards");
        $player1Points = $this->assertInt($session->get("player_points_1"), "player_points_1");
        $player2Points = $this->assertInt($session->get("player_points_2"), "player_points_2");

        $dealerPoints = $this->rules->countPoints($dealerCards);
        while ($dealerPoints < 16) {
            $newCard = array_shift($cards);
            $dealerCards[] = $newCard;
            $dealerPoints = $this->rules->countPoints($dealerCards);
        }

        $winner1 = $this->rules->decideWinner($dealerPoints, $player1Points, $session);
        $winner2 = $this->rules->decideWinner($dealerPoints, $player2Points, $session);

        $session->set("shuffled_deck", $cards);
        $session->set("dealer_cards", $dealerCards);
        $session->set("dealer_points", $dealerPoints);

        return $this->render("black_jack/winner_split.html.twig", [
            "dealer" => $dealerCards,
            "dealerPoints" => $dealerPoints,
            "hand1" => $session->get("hand1", []),
            "hand2" => $session->get("hand2", []),
            "totplayer1" => $player1Points,
            "totplayer2" => $player2Points,
            "winner1" => $winner1,
            "winner2" => $winner2,
            "splittat" => true,
            "coins" => $session->get("coins", 100)
        ]);
    }




    #[Route('/split', name: 'split')]
    public function split(SessionInterface $session): Response
    {
        $session->set("is_split", true);

        $cards = $this->assertArray($session->get("shuffled_deck"), "shuffled_deck");
        $playerCards = $this->assertArray($session->get("player_cards"), "player_cards");

        $hand1 = [$playerCards[0]];
        $hand2 = [$playerCards[1]];

        $hand1[] = array_shift($cards);
        $hand2[] = array_shift($cards);

        $hand1points = $this->rules->countPoints($hand1);
        $hand2points = $this->rules->countPoints($hand2);

        $session->set("hand1", $hand1);
        $session->set("hand2", $hand2);
        $session->set("player_points_1", $hand1points);
        $session->set("player_points_2", $hand2points);
        $session->set("active_hand", "hand1");
        $session->set("shuffled_deck", $cards);
        $session->set("active_hand", "hand1");

        return $this->render("black_jack/game_split.html.twig", [
            "dealer" => $session->get("dealer_cards", []),
            "dealerPoints" => $session->get("dealer_points", []),
            "hand1" => $hand1,
            "hand2" => $hand2,
            "splittat" => true,
            "split" => false,
            "totplayer1" => $hand1points,
            "totplayer2" => $hand2points,
            "active" => "hand1",
            "coins" => $session->get("coins", 100)
        ]);
    }
}
