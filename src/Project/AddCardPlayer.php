<?php

namespace App\Project;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class AddCardPlayer
{
    private EntityManagerInterface $em;
    private Security $security;
    private DecideWinner $decideWinner;

    public function __construct(EntityManagerInterface $em, Security $security, DecideWinner $decideWinner)
    {
        $this->em = $em;
        $this->security = $security;
        $this->decideWinner = $decideWinner;
    }

    public function addCard(Data $data, string $player, string $hand, bool $isDouble = false): bool
    {
        $players = $data->get('players', []);
        $cards = $data->get('deck_of_cards');
        $rules = new Rules();
    
        if (empty($cards) || !isset($players[$player]['hands'][$hand])) {
            return false;
        }
    
        $addedCard = array_splice($cards, 0, 1);
        $players[$player]['hands'][$hand]['cards'][] = $addedCard[0];
        $players[$player]['hands'][$hand]['points'] = $rules->countPoints($players[$player]['hands'][$hand]['cards']);

        if ($players[$player]['hands'][$hand]['points'] > 21) {
            $players[$player]['hands'][$hand]['status'] = 'bust';
        } elseif ($isDouble) {
            $players[$player]['hands'][$hand]['status'] = 'stand';
        } else {
            $players[$player]['hands'][$hand]['status'] = 'active';
        }
    
        $data->set('deck_of_cards', $cards);
        $data->set('players', $players);
        $data->save();
    
        return $players[$player]['hands'][$hand]['points'] > 21;
    }

    public function activateNext(Data $data, string $currentPlayer, string $currentHand): bool
    {
        $players = $data->get('players', []);
        $foundCurrent = false;
    
        foreach ($players as $playerName => $player) {
            foreach ($player['hands'] as $handName => $hand) {
                if (!$foundCurrent) {
                    if ($playerName === $currentPlayer && $handName === $currentHand) {
                        $foundCurrent = true;
                    }
                    continue;
                }
                if ($hand['status'] === 'waiting') {
                    $players[$playerName]['hands'][$handName]['status'] = 'active';
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
        $hasNext = $this->activateNext($data, $player, $hand);
        return !$hasNext;
    }
}