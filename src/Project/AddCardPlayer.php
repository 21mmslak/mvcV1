<?php

namespace App\Project;

use App\Project\AddCardDealer;
use App\Project\Rules;
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

    public function addCard(Data $data, string $player, string $hand): bool
    {
        $players = $data->get('players', []);
        $cards = $data->get('deck_of_cards');
        $rules = new Rules();
    
        if (!empty($cards)) {
            $addedCard = array_splice($cards, 0, 1);
            $players[$player]['hands'][$hand]['cards'][] = $addedCard[0];
            $players[$player]['hands'][$hand]['points'] = $rules->countPoints($players[$player]['hands'][$hand]['cards']);
    
            $data->set('deck_of_cards', $cards);
            $data->set('players', $players);
            $data->save();
    
            if ($players[$player]['hands'][$hand]['points'] > 21) {
                $players[$player]['hands'][$hand]['status'] = 'bust';

                $playerNames = array_keys($players);
                $currentPlayerIndex = array_search($player, $playerNames);
    
                for ($i = $currentPlayerIndex; $i < count($playerNames); $i++) {
                    $currentPlayer = $playerNames[$i];
                    $hands = array_keys($players[$currentPlayer]['hands']);
                    $startHandIndex = ($i == $currentPlayerIndex) ? array_search($hand, $hands) + 1 : 0;
    
                    for ($j = $startHandIndex; $j < count($hands); $j++) {
                        if (($players[$currentPlayer]['hands'][$hands[$j]]['status'] ?? '') === 'active') {
                            $data->set('active_player', $currentPlayer);
                            $data->set('active_hand', $hands[$j]);
                            $data->save();
                            return false;
                        }
                    }
                }

                $data->set('game_started', false);
                $data->set('active_player', null);
                $data->set('active_hand', null);
    
                $addDealer = new AddCardDealer();
                $addDealer->addCardDealer($data);
    
                $this->decideWinner->decideWinner($data);
    
                $data->set('game_over', true);
                $data->save();
                return true;
            }
        }
    
        return false;
    }
}