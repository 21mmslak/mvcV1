<?php

namespace App\Project;

use App\Entity\Scoreboard;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;

class DecideWinner
{
    private Security $security;
    private EntityManagerInterface $em;

    public function __construct(Security $security, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->em = $em;
    }

    public function decideWinner(Data $data): void
    {
        if ($data->get('game_started')) {
            $data->set('game_started', false);
            $data->save();
        }
    
        $dealerPoints = (int) $data->get('dealer_points');
        $players = $data->get('players', []);
        $user = $this->security->getUser();
        $coins = $this->getUserCoins($user, $data);
    
        foreach ($players as $name => &$player) {
            foreach ($player['hands'] as $handName => &$hand) {
                $bet = $hand['bet'] ?? 10;
                $result = $this->evaluateHand($hand, $dealerPoints, $bet, $coins);
                $coins += $result['coinsDelta'];
                $hand['result'] = $result['message'];
            }
        }
    
        $this->updateCoins($user, $coins, $data);
        $data->set('players', $players);
        $data->save();
    }

    private function getUserCoins($user, Data $data): int
    {
        if ($user instanceof User) {
            $scoreboard = $this->em->getRepository(Scoreboard::class)->findOneBy(['user' => $user]);
            if ($scoreboard) {
                return $scoreboard->getCoins();
            }
        }
        return (int) $data->get('coins', 5000);
    }

    private function evaluateHand(array $hand, int $dealerPoints, int $bet, int $coins): array
    {
        $playerPoints = (int) ($hand['points'] ?? 0);
        $cards = $hand['cards'] ?? [];
        $bonus = 0;

        if (count($cards) === 2) {
            $rules = new \App\Project\Rules();
            if ($rules->countPoints($cards) === 21) {
                $bonus = (int) round($bet * 1.5);
                return [
                    'coinsDelta' => $bonus,
                    'message' => "Blackjack! Player wins {$bonus} coins."
                ];
            }
        }

        if ($playerPoints > 21) {
            return [
                'coinsDelta' => -$bet,
                'message' => "Dealer wins (bust)."
            ];
        }
        if ($dealerPoints > 21 || $playerPoints > $dealerPoints) {
            return [
                'coinsDelta' => $bet,
                'message' => "Player wins!"
            ];
        }
        if ($dealerPoints > $playerPoints) {
            return [
                'coinsDelta' => -$bet,
                'message' => "Dealer wins."
            ];
        }
        return [
            'coinsDelta' => 0,
            'message' => "Draw."
        ];
    }

    private function updateCoins($user, int $coins, Data $data): void
    {
        if ($user instanceof User) {
            $scoreboard = $this->em->getRepository(Scoreboard::class)->findOneBy(['user' => $user]);
            if ($scoreboard) {
                $scoreboard->setCoins($coins);
                $this->em->persist($scoreboard);
                $this->em->flush();
                return;
            }
        }
        $data->set('coins', $coins);
    }
}