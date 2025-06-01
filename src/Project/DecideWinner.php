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
    
        $dealerPoints = is_numeric($data->get('dealer_points')) ? (int) $data->get('dealer_points') : 0;
        $players = $data->get('players');
        $players = is_array($players) ? $players : [];
    
        $user = $this->security->getUser();
        $coins = $this->getUserCoins($user instanceof User ? $user : null, $data);
    
        foreach ($players as $playerName => $playerDataRaw) {
            if (!is_array($playerDataRaw) || !isset($playerDataRaw['hands']) || !is_array($playerDataRaw['hands'])) {
                continue;
            }
    
            foreach ($playerDataRaw['hands'] as $handName => $handData) {
                if (!is_array($handData)) {
                    continue;
                }
    
                $bet = isset($handData['bet']) && is_numeric($handData['bet']) ? (int) $handData['bet'] : 10;
                $result = $this->evaluateHand($handData, $dealerPoints, $bet, $coins);
                $coins += $result['coinsDelta'];
    
                $players[$playerName]['hands'][$handName]['result'] = $result['message'];
            }
        }
    
        $this->updateCoins($user instanceof User ? $user : null, $coins, $data);
        $data->set('players', $players);
        $data->save();
    }

    /**
     * @param User|null $user
     * @param Data $data
     * @return int
     */
    private function getUserCoins(?User $user, Data $data): int
    {
        if ($user instanceof User) {
            $scoreboard = $this->em->getRepository(Scoreboard::class)->findOneBy(['user' => $user]);
            if ($scoreboard) {
                return (int) ($scoreboard->getCoins() ?? 5000);
            }
        }
        $coins = $data->get('coins', 5000);
        return is_numeric($coins) ? (int) $coins : 5000;
    }

    /**
     * @param array<string, mixed> $hand
     * @param int $dealerPoints
     * @param int $bet
     * @param int $coins
     * @return array{coinsDelta: int, message: string}
     */
    private function evaluateHand(array $hand, int $dealerPoints, int $bet, int $coins): array
    {
        $playerPoints = isset($hand['points']) && is_numeric($hand['points']) ? (int) $hand['points'] : 0;
        $cards = isset($hand['cards']) && is_array($hand['cards']) ? $hand['cards'] : [];
        $bonus = 0;

        if (count($cards) === 2) {
            $validCards = array_filter($cards, function ($card) {
                return is_array($card) && isset($card['value']) && isset($card['suit']);
            });
        
            if (count($validCards) === 2) {
                $rules = new \App\Project\Rules();
                if ($rules->countPoints($validCards) === 21) {
                    $bonus = (int) round($bet * 1.5);
                    return [
                        'coinsDelta' => $bonus,
                        'message' => "Blackjack! Player wins {$bonus} coins."
                    ];
                }
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

    /**
     * @param User|null $user
     * @param int $coins
     * @param Data $data
     * @return void
     */
    private function updateCoins(?User $user, int $coins, Data $data): void
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