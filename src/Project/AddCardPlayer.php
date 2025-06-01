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
        $playersRaw = $data->get('players', []);
        $players = is_array($playersRaw) ? $playersRaw : [];

        if (!isset($players[$player]) || !is_array($players[$player])) {
            return false;
        }

        if (!isset($players[$player]['hands']) || !is_array($players[$player]['hands'])) {
            return false;
        }

        if (!isset($players[$player]['hands'][$hand]) || !is_array($players[$player]['hands'][$hand])) {
            return false;
        }

        $handData = &$players[$player]['hands'][$hand];

        if (!isset($handData['cards']) || !is_array($handData['cards'])) {
            $handData['cards'] = [];
        }

        $deckRaw = $data->get('deck_of_cards', []);
        $deck = is_array($deckRaw) ? $deckRaw : [];

        if (empty($deck)) {
            return false;
        }

        $addedCard = array_shift($deck);
        if (!is_array($addedCard)) {
            return false;
        }

        $handData['cards'][] = $addedCard;

        $rules = new Rules();
        $points = $rules->countPoints($handData['cards']);
        $handData['points'] = $points;

        if ($points > 21) {
            $handData['status'] = 'bust';
        } elseif ($isDouble) {
            $handData['status'] = 'stand';
        } else {
            $handData['status'] = 'active';
        }

        $data->set('deck_of_cards', $deck);
        $data->set('players', $players);
        $data->save();

        return $points > 21;
    }

    public function activateNext(Data $data, string $currentPlayer, string $currentHand): bool
    {
        $playersRaw = $data->get('players', []);
        $players = is_array($playersRaw) ? $playersRaw : [];

        $foundCurrent = false;

        foreach ($players as $playerName => $player) {
            if (!is_array($player) || !isset($player['hands']) || !is_array($player['hands'])) {
                continue;
            }
        
            foreach ($player['hands'] as $handName => $hand) {
                if (!is_array($hand)) {
                    continue;
                }
        
                if (isset($players[$playerName]) && is_array($players[$playerName]) &&
                    isset($players[$playerName]['hands']) && is_array($players[$playerName]['hands']) &&
                    isset($players[$playerName]['hands'][$handName]) && is_array($players[$playerName]['hands'][$handName]) &&
                    isset($players[$playerName]['hands'][$handName]['status']) && $players[$playerName]['hands'][$handName]['status'] === 'waiting'
                ) {
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
        return !$this->activateNext($data, $player, $hand);
    }
}