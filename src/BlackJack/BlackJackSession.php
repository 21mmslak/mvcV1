<?php

namespace App\BlackJack;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use LogicException;

class BlackJackSession
{
    public function getPlayerCards(SessionInterface $session): array
    {
        return $this->getArray($session, "player_cards");
    }

    public function setPlayerCards(array $cards, SessionInterface $session): void
    {
        $session->set("player_cards", $cards);
    }

    public function getDealerCards(SessionInterface $session): array
    {
        return $this->getArray($session, "dealer_cards");
    }

    public function setDealerCards(array $cards, SessionInterface $session): void
    {
        $session->set("dealer_cards", $cards);
    }

    public function getDeck(SessionInterface $session): array
    {
        return $this->getArray($session, "shuffled_deck");
    }

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

    public function getHand(SessionInterface $session, string $hand): array
    {
        return $this->getArray($session, $hand);
    }

    public function setHand(SessionInterface $session, string $hand, array $cards): void
    {
        $session->set($hand, $cards);
    }

    public function getCoins(SessionInterface $session): int
    {
        return $session->get("coins", 100);
    }

    public function setCoins(SessionInterface $session, int $coins): void
    {
        $session->set("coins", $coins);
    }

    public function getActiveHand(SessionInterface $session): string
    {
        return $session->get("active_hand", "hand1");
    }

    public function setActiveHand(SessionInterface $session, string $hand): void
    {
        $session->set("active_hand", $hand);
    }

    private function getArray(SessionInterface $session, string $key): array
    {
        $value = $session->get($key, []);
        if (!is_array($value)) {
            throw new LogicException("Expected array for $key");
        }
        return $value;
    }

    private function getInt(SessionInterface $session, string $key): int
    {
        $value = $session->get($key);
        if (!is_int($value)) {
            throw new LogicException("Expected int for $key");
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