<?php

namespace App\BlackJack;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use LogicException;
use App\Card\Card;

class BlackJackSession
{
    /**
     * @return array<int, Card>
     */
    public function getPlayerCards(SessionInterface $session): array
    {
        $value = $session->get("player_cards", []);
        if (!is_array($value)) {
            throw new LogicException("Expected array for player_cards");
        }
        /** @var array<int, Card> */
        return $value;
    }

    /**
     * @param array<int, Card> $cards
     */
    public function setPlayerCards(array $cards, SessionInterface $session): void
    {
        $session->set("player_cards", $cards);
    }

    /**
     * @return array<int, Card>
     */
    public function getDealerCards(SessionInterface $session): array
    {
        $value = $session->get("dealer_cards", []);
        if (!is_array($value)) {
            throw new LogicException("Expected array for dealer_cards");
        }
        /** @var array<int, Card> */
        return $value;
    }

    /**
     * @param array<int, array{value: string, suit: string, card: string}> $cards
     */
    public function setDealerCards(array $cards, SessionInterface $session): void
    {
        $session->set("dealer_cards", $cards);
    }

    /**
     * @return array<int, Card>
     */
    public function getDeck(SessionInterface $session): array
    {
        $value = $session->get("shuffled_deck", []);
        if (!is_array($value)) {
            throw new LogicException("Expected array for shuffled_deck");
        }
        /** @var array<int, Card> */
        return $value;
    }

    /**
     * @param array<int, Card> $cards
     */
    public function setDeck(array $cards, SessionInterface $session): void
    {
        $session->set("shuffled_deck", $cards);
    }

    public function getPoints(SessionInterface $session, string $key): int
    {
        return $this->getInt($session, $key);
    }

    public function setPoints(SessionInterface $session, string $key, int $points): void
    {
        $session->set($key, $points);
    }

    /**
     * @return array<int, array{value: string, suit: string, card: string}>
     */
    public function getHand(SessionInterface $session, string $hand): array
    {
        $value = $session->get($hand, []);
        if (!is_array($value)) {
            throw new LogicException("Expected array for $hand");
        }
        /** @var array<int, array{value: string, suit: string, card: string}> */
        return $value;
    }

    /**
     * @param array<int, array{value: string, suit: string, card: string}> $cards
     */
    public function setHand(SessionInterface $session, string $hand, array $cards): void
    {
        $session->set($hand, $cards);
    }

    public function getCoins(SessionInterface $session): int
    {
        return $this->getInt($session, "coins");
    }

    public function setCoins(SessionInterface $session, int $coins): void
    {
        $session->set("coins", $coins);
    }

    public function getActiveHand(SessionInterface $session): string
    {
        return $this->getString($session, "active_hand");
    }

    public function setActiveHand(SessionInterface $session, string $hand): void
    {
        $session->set("active_hand", $hand);
    }

    private function getInt(SessionInterface $session, string $key): int
    {
        $value = $session->get($key);
        if (!is_int($value)) {
            throw new LogicException("Expected int for $key");
        }
        return $value;
    }

    private function getString(SessionInterface $session, string $key): string
    {
        $value = $session->get($key);
        if (!is_string($value)) {
            throw new LogicException("Expected string for $key");
        }
        return $value;
    }

    public function get(SessionInterface $session, string $key, mixed $default = null): mixed
    {
        return $session->get($key, $default);
    }

    public function set(SessionInterface $session, string $key, mixed $value): void
    {
        $session->set($key, $value);
    }
}
