<?php

namespace App\Project;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class AddCardPlayer
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private DecideWinner $decideWinner
    ) {}

    public function addCard(Data $data, string $player, string $hand, bool $isDouble = false): bool
    {
        $players = $this->getArray($data->get('players'));
        $deck = $this->getArray($data->get('deck_of_cards'));

        if (!isset($players[$player]['hands'][$hand]['cards'])) {
            return false;
        }

        $addedCard = array_shift($deck);
        if (!is_array($addedCard)) {
            return false;
        }

        $players[$player]['hands'][$hand]['cards'][] = $addedCard;

        $rules = new Rules();
        $points = $rules->countPoints($players[$player]['hands'][$hand]['cards']);
        $players[$player]['hands'][$hand]['points'] = $points;

        $status = match (true) {
            $points > 21 => 'bust',
            $isDouble => 'stand',
            default => 'active',
        };
        $players[$player]['hands'][$hand]['status'] = $status;

        $data->set('deck_of_cards', $deck);
        $data->set('players', $players);
        $data->save();

        return $points > 21;
    }

    public function activateNext(Data $data, string $currentPlayer, string $currentHand): bool
    {
        $players = $this->getArray($data->get('players'));
        $foundCurrent = false;

        foreach ($players as $playerName => &$player) {
            if (!isset($player['hands']) || !is_array($player['hands'])) {
                continue;
            }
            foreach ($player['hands'] as $handName => &$hand) {
                if (!$foundCurrent && $playerName === $currentPlayer && $handName === $currentHand) {
                    $foundCurrent = true;
                    continue;
                }
                if ($foundCurrent && ($hand['status'] ?? '') === 'waiting') {
                    $hand['status'] = 'active';
                    $data->set('players', $players);
                    $data->set('active_player', $playerName);
                    $data->set('active_hand', $handName);
                    $data->save();
                    return true;
                }
            }
        }

        $data->set('active_player', null);
        $data->set('active_hand', null);
        $data->save();
        return false;
    }

    public function checkAndHandleGameOver(Data $data, string $player, string $hand): bool
    {
        return !$this->activateNext($data, $player, $hand);
    }

    /**
     * Ensures the provided input is an array.
     * @param mixed $input
     * @return array
     */
    private function getArray(mixed $input): array
    {
        return is_array($input) ? $input : [];
    }
}
