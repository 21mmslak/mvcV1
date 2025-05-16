<?php

namespace App\BlackJack;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class BlackJackService
{
    private BlackJack $blackJack;

    public function __construct(BlackJack $blackJack)
    {
        $this->blackJack = $blackJack;
    }

    public function startGame(SessionInterface $session): void
    {
        $cards = $this->blackJack->startGame();
        $session->set("shuffled_deck", $cards);
    }
}
