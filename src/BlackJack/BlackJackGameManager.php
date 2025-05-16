<?php

namespace App\BlackJack;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class BlackJackGameManager
{
    private BlackJackRules $rules;
    private BlackJackSession $state;
    private BlackJackService $service;

    public function __construct(
        BlackJackRules $rules,
        BlackJackSession $state,
        BlackJackService $service
    ) {
        $this->rules = $rules;
        $this->state = $state;
        $this->service = $service;
    }

    public function startNewGame(SessionInterface $session): array
    {
        if ($session->get('game_started', false)) {
            return [
                'playerCards' => $this->state->getPlayerCards($session),
                'dealerCards' => $this->state->getDealerCards($session),
                'playerPoints' => $this->state->getPoints($session, 'player_points'),
                'dealerPoints' => $this->state->getPoints($session, 'dealer_points'),
                'isOver' => $this->rules->checkOver(
                    $this->state->getPoints($session, 'player_points')
                ),
            ];
        }

        $this->service->startGame($session);
        $cards = $this->state->getDeck($session);

        $playerCards = [array_shift($cards), array_shift($cards)];
        $dealerCards = [array_shift($cards), array_shift($cards)];

        $playerPoints = $this->rules->countPoints($playerCards);
        $dealerPoints = $this->rules->countPoints($dealerCards);

        $this->state->setPlayerCards($playerCards, $session);
        $this->state->setDealerCards($dealerCards, $session);
        $this->state->setDeck($cards, $session);
        $this->state->setPoints($session, 'player_points', $playerPoints);
        $this->state->setPoints($session, 'dealer_points', $dealerPoints);
        $session->set('game_started', true);

        return [
            'playerCards' => $playerCards,
            'dealerCards' => $dealerCards,
            'dealerPoints' => $dealerPoints,
            'playerPoints' => $playerPoints,
            'isOver' => $this->rules->checkOver($playerPoints),
        ];
    }

    public function addCardToPlayer(SessionInterface $session): array
    {
        $cards = $this->state->getDeck($session);
        $newCard = array_shift($cards);

        $playerCards = $this->state->getPlayerCards($session);
        $dealerPoints = $this->state->getPoints($session, 'dealer_points');

        if ($this->rules->countPoints($playerCards) < 21) {
            $playerCards[] = $newCard;
        }

        $playerPoints = $this->rules->countPoints($playerCards);

        $this->state->setDeck($cards, $session);
        $this->state->setPlayerCards($playerCards, $session);
        $this->state->setPoints($session, 'player_points', $playerPoints);

        return [
            'playerCards' => $playerCards,
            'dealerCards' => $this->state->getDealerCards($session),
            'dealerPoints' => $dealerPoints,
            'playerPoints' => $playerPoints,
            'isOver' => $this->rules->checkOver($playerPoints),
        ];
    }

    public function addCardToSplitHand(SessionInterface $session): array
    {
        $cards = $this->state->getDeck($session);
        $activeHand = $this->state->getActiveHand($session);
        $newCard = array_shift($cards);

        $hand1 = $this->state->getHand($session, "hand1");
        $hand2 = $this->state->getHand($session, "hand2");
        $dealerCards = $this->state->getDealerCards($session);
        $dealerPoints = $this->state->getPoints($session, "dealer_points");

        $shouldRedirect = false;

        if ($activeHand === "hand1") {
            $hand1[] = $newCard;
            $points1 = $this->rules->countPoints($hand1);
            $this->state->setHand($session, "hand1", $hand1);
            $this->state->setPoints($session, "player_points_1", $points1);

            if ($points1 >= 21) {
                $this->state->setActiveHand($session, "hand2");
            }
        } else {
            $hand2[] = $newCard;
            $points2 = $this->rules->countPoints($hand2);
            $this->state->setHand($session, "hand2", $hand2);
            $this->state->setPoints($session, "player_points_2", $points2);

            if ($points2 >= 20) {
                $shouldRedirect = true;
            }
        }

        $this->state->setDeck($cards, $session);

        return [
            'dealerCards' => $dealerCards,
            'dealerPoints' => $dealerPoints,
            'hand1' => $hand1,
            'hand2' => $hand2,
            'playerPoints1' => $this->state->getPoints($session, "player_points_1"),
            'playerPoints2' => $this->state->getPoints($session, "player_points_2"),
            'activeHand' => $this->state->getActiveHand($session),
            'shouldRedirect' => $shouldRedirect
        ];
    }

    public function stand(SessionInterface $session): array
    {
        $cards = $this->state->getDeck($session);
        $dealerCards = $this->state->getDealerCards($session);
        $dealerPoints = $this->rules->countPoints($dealerCards);

        while ($dealerPoints < 16) {
            $newCard = array_shift($cards);
            $dealerCards[] = $newCard;
            $dealerPoints = $this->rules->countPoints($dealerCards);
        }

        $this->state->setDealerCards($dealerCards, $session);
        $this->state->setDeck($cards, $session);
        $this->state->setPoints($session, 'dealer_points', $dealerPoints);
        $session->set('game_started', false);

        $playerCards = $this->state->getPlayerCards($session);
        $playerPoints = $this->state->getPoints($session, 'player_points');
        $winner = $this->rules->decideWinner($dealerPoints, $playerPoints, $session);

        $this->resetGame($session);

        return [
            'dealerCards' => $dealerCards,
            'dealerPoints' => $dealerPoints,
            'playerCards' => $playerCards,
            'playerPoints' => $playerPoints,
            'winner' => $winner
        ];
    }

    public function standSplit(SessionInterface $session): array|null
    {
        $activeHand = $this->state->getActiveHand($session);

        if ($activeHand === "hand1") {
            $this->state->setActiveHand($session, "hand2");

            return null;
        }

        $cards = $this->state->getDeck($session);
        $dealerCards = $this->state->getDealerCards($session);
        $player1Points = $this->state->getPoints($session, "player_points_1");
        $player2Points = $this->state->getPoints($session, "player_points_2");

        $dealerPoints = $this->rules->countPoints($dealerCards);
        while ($dealerPoints < 16) {
            $newCard = array_shift($cards);
            $dealerCards[] = $newCard;
            $dealerPoints = $this->rules->countPoints($dealerCards);
        }

        $winner1 = $this->rules->decideWinner($dealerPoints, $player1Points, $session);
        $winner2 = $this->rules->decideWinner($dealerPoints, $player2Points, $session);

        $this->state->setDeck($cards, $session);
        $this->state->setDealerCards($dealerCards, $session);
        $this->state->setPoints($session, "dealer_points", $dealerPoints);

        $this->resetGame($session);

        return [
            'dealerCards' => $dealerCards,
            'dealerPoints' => $dealerPoints,
            'hand1' => $this->state->getHand($session, "hand1"),
            'hand2' => $this->state->getHand($session, "hand2"),
            'points1' => $player1Points,
            'points2' => $player2Points,
            'winner1' => $winner1,
            'winner2' => $winner2,
        ];
    }

    public function resetGame(SessionInterface $session): void
    {
        $session->set('game_started', false);

        foreach ([
            'player_cards',
            'dealer_cards',
            'deck',
            'player_points',
            'dealer_points',
            'is_split',
            'hand1',
            'hand2',
            'player_points_1',
            'player_points_2',
            'active_hand',
        ] as $key) {
            $session->remove($key);
        }
    }

    public function split(SessionInterface $session): array
    {
        $this->state->set($session, "is_split", true);
    
        $cards = $this->state->getDeck($session);
        $playerCards = $this->state->getPlayerCards($session);
    
        $hand1 = [$playerCards[0], array_shift($cards)];
        $hand2 = [$playerCards[1], array_shift($cards)];
    
        $points1 = $this->rules->countPoints($hand1);
        $points2 = $this->rules->countPoints($hand2);
    
        $this->state->setHand($session, "hand1", $hand1);
        $this->state->setHand($session, "hand2", $hand2);
        $this->state->setPoints($session, "player_points_1", $points1);
        $this->state->setPoints($session, "player_points_2", $points2);
        $this->state->setActiveHand($session, "hand1");
        $this->state->setDeck($cards, $session);
    
        return [
            'dealerCards' => $this->state->getDealerCards($session),
            'dealerPoints' => $this->state->getPoints($session, "dealer_points"),
            'hand1' => $hand1,
            'hand2' => $hand2,
            'points1' => $points1,
            'points2' => $points2,
            'active' => "hand1"
        ];
    }
}