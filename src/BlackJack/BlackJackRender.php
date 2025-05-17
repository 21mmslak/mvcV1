<?php

namespace App\BlackJack;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

class BlackJackRender
{
    private Environment $twig;
    private BlackJackSession $state;

    public function __construct(Environment $twig, BlackJackSession $state)
    {
        $this->twig = $twig;
        $this->state = $state;
    }

    /**
     * @param array<int, array{value: string, suit: string, card: string}> $playerCards
     * @param array<int, array{value: string, suit: string, card: string}> $dealerCards
     */
    public function renderGameStart(
        array $playerCards,
        array $dealerCards,
        int $dealerPoints,
        int $playerPoints,
        bool $split,
        SessionInterface $session
    ): Response {
        return new Response($this->twig->render("black_jack/game_start.html.twig", [
            "player" => $playerCards,
            "dealer" => $dealerCards,
            "dealerPoints" => $dealerPoints,
            "playerPoints" => $playerPoints,
            "split" => $split,
            "splittat" => false,
            "coins" => $this->state->getCoins($session)
        ]));
    }

    /**
     * @param array<int, array{value: string, suit: string, card: string}> $dealerCards
     * @param array<int, array{value: string, suit: string, card: string}> $playerCards
     */
    public function renderWinner(
        string $winner,
        array $dealerCards,
        array $playerCards,
        int $dealerPoints,
        int $playerPoints,
        SessionInterface $session
    ): Response {
        return new Response($this->twig->render("black_jack/winner.html.twig", [
            "winner" => $winner,
            "dealer" => $dealerCards,
            "player" => $playerCards,
            "dealerPoints" => $dealerPoints,
            "playerPoints" => $playerPoints,
            "coins" => $this->state->getCoins($session)
        ]));
    }

    /**
     * @param array<int, array{value: string, suit: string, card: string}> $dealerCards
     * @param array<int, array{value: string, suit: string, card: string}> $hand1
     * @param array<int, array{value: string, suit: string, card: string}> $hand2
     */
    public function renderGameSplit(
        array $dealerCards,
        int $dealerPoints,
        array $hand1,
        array $hand2,
        int $points1,
        int $points2,
        string $activeHand,
        SessionInterface $session
    ): Response {
        return new Response($this->twig->render("black_jack/game_split.html.twig", [
            "dealer" => $dealerCards,
            "dealerPoints" => $dealerPoints,
            "hand1" => $hand1,
            "hand2" => $hand2,
            "totplayer1" => $points1,
            "totplayer2" => $points2,
            "splittat" => true,
            "split" => false,
            "active" => $activeHand,
            "coins" => $this->state->getCoins($session)
        ]));
    }

    /**
     * @param array<int, array{value: string, suit: string, card: string}> $dealerCards
     * @param array<int, array{value: string, suit: string, card: string}> $hand1
     * @param array<int, array{value: string, suit: string, card: string}> $hand2
     */
    public function renderWinnerSplit(
        array $dealerCards,
        int $dealerPoints,
        array $hand1,
        array $hand2,
        int $points1,
        int $points2,
        string $winner1,
        string $winner2,
        SessionInterface $session
    ): Response {
        return new Response($this->twig->render("black_jack/winner_split.html.twig", [
            "dealer" => $dealerCards,
            "dealerPoints" => $dealerPoints,
            "hand1" => $hand1,
            "hand2" => $hand2,
            "totplayer1" => $points1,
            "totplayer2" => $points2,
            "winner1" => $winner1,
            "winner2" => $winner2,
            "splittat" => true,
            "coins" => $this->state->getCoins($session)
        ]));
    }
}
