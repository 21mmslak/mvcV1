<?php

namespace App\Project;

use App\Project\AddCardDealer;
use App\Project\Rules;

class AddCardPlayer
{

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

                // $nextFound = false;
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
    
                $winner = new DecideWinner();
                $winner->decideWinner($data);
    
                $data->set('game_over', true);
                $data->save();
                return true;
            }
        }
    
        return false;
    }


    // public function addCard(Data $data): void
    // {
    //     $game = $data->get('game_started');
    //     $activePlayer = $data->get('active_player');
    
    //     if (!$game || !$activePlayer) return;
    
    //     $rules = new Rules();
    //     $cards = $data->get('deck_of_cards');
    //     $players = $data->get('players');
    
    //     if (!empty($cards)) {
    //         $addedCard = array_splice($cards, 0, 1);
    //         $players[$activePlayer]['cards'][] = $addedCard[0];
    //         $players[$activePlayer]['points'] = $rules->countPoints($players[$activePlayer]['cards']);
    
    //         if ($players[$activePlayer]['points'] > 21) {
    //             $players[$activePlayer]['status'] = 'bust';
    
    //             $keys = array_keys($players);
    //             $index = array_search($activePlayer, $keys);
    //             $next = null;
    
    //             for ($i = $index + 1; $i < count($keys); $i++) {
    //                 if ($players[$keys[$i]]['status'] == 'active') {
    //                     $next = $keys[$i];
    //                     break;
    //                 }
    //             }
    
    //             if ($next) {
    //                 $data->set('active_player', $next);
    //             } else {
    //                 $data->set('active_player', null);
    //                 $data->set('game_started', false);
    
    //                 $addDealer = new AddCardDealer();
    //                 $addDealer->addCardDealer($data);
    
    //                 $winner = new DecideWinner();
    //                 $winner->decideWinner($data);
    //             }
    //         }
    //     }
    
    //     $data->set('deck_of_cards', $cards);
    //     $data->set('players', $players);
    //     $data->save();
    // }

    // public function addCardSplit(Data $data): void
    // {
    //     $game = $data->get('game_started');
    //     $active = $data->get('active_hand');

    //     if (!$game) {
    //         return;
    //     }

    //     $rules = new Rules();
    //     $cards = $data->get('deck_of_cards');

    //     if (!empty($cards)) {
    //         $addedCard = array_splice($cards, 0, 1);

    //         if ($active === 'hand1') {
    //             $hand1 = $data->get('hand1', []);
    //             $hand1[] = $addedCard[0];
    //             $hand1Points = $rules->countPoints($hand1);
    //             $data->set('hand1', $hand1);
    //             $data->set('hand1Points', $hand1Points);

    //             if ($hand1Points > 20) {
    //                 $data->set('active_hand', 'hand2');
    //             }
    //         } elseif ($active === 'hand2') {
    //             $hand2 = $data->get('hand2', []);
    //             $hand2[] = $addedCard[0];
    //             $hand2Points = $rules->countPoints($hand2);
    //             $data->set('hand2', $hand2);
    //             $data->set('hand2Points', $hand2Points);

    //             if ($hand2Points > 20) {
    //                 $data->set('game_started', false);
    //                 $data->set('active_hand', false);

    //                 $addDealer = new AddCardDealer();
    //                 $addDealer->addCardDealer($data);
    //             }
    //         }

    //         $data->set('deck_of_cards', $cards);
    //     }

    //     $data->save();
    // }
}
