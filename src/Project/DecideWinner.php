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

        $dealerPoints = $this->getInt($data->get('dealer_points'), 0);
        $players = $this->getArray($data->get('players'));

        $user = $this->security->getUser();
        $coins = $this->getUserCoins($user instanceof User ? $user : null, $data);

        foreach ($players as $playerName => &$player) {
            $playerHands = $this->getArray($player['hands'] ?? []);
            foreach ($playerHands as $handName => &$hand) {
                $bet = $this->getInt($hand['bet'] ?? null, 10);
                $result = $this->evaluateHand($this->getArray($hand), $dealerPoints, $bet, $coins);
                $coins += $result['coinsDelta'];
                $hand['result'] = $result['message'];
            }
            $player['hands'] = $playerHands;
        }

        $this->updateCoins($user instanceof User ? $user : null, $coins, $data);
        $data->set('players', $players);
        $data->save();
    }

    private function getUserCoins(?User $user, Data $data): int
    {
        if ($user instanceof User) {
            $scoreboard = $this->em->getRepository(Scoreboard::class)->findOneBy(['user' => $user]);
            if ($scoreboard) {
                return $this->getInt($scoreboard->getCoins(), 5000);
            }
        }
        return $this->getInt($data->get('coins'), 5000);
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
        $points = $this->getInt($hand['points'] ?? null, 0);
        $cards = $this->getArray($hand['cards'] ?? []);
        $bonus = 0;

        if (count($cards) === 2) {
            $validCards = array_filter($cards, fn($card) => is_array($card) && isset($card['value'], $card['suit']));
            if (count($validCards) === 2) {
                $rules = new Rules();
                if ($rules->countPoints($validCards) === 21) {
                    $bonus = (int) round($bet * 1.5);
                    return ['coinsDelta' => $bonus, 'message' => "Blackjack! Player wins {$bonus} coins."];
                }
            }
        }

        if ($points > 21) {
            return ['coinsDelta' => -$bet, 'message' => "Dealer wins (bust)."];
        }
        if ($dealerPoints > 21 || $points > $dealerPoints) {
            return ['coinsDelta' => $bet, 'message' => "Player wins!"];
        }
        if ($dealerPoints > $points) {
            return ['coinsDelta' => -$bet, 'message' => "Dealer wins."];
        }
        return ['coinsDelta' => 0, 'message' => "Draw."];
    }

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

    /**
     * @param mixed $value
     * @param int $default
     * @return int
     */
    private function getInt(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param mixed $value
     * @return array
     */
    private function getArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}