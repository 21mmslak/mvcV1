<?php

namespace App\Project;

use App\Project\Rules;

class Split
{
    /**
     * Splits a player's hand into two hands.
     *
     * @param Data $data
     * @param string $player
     * @param string $hand
     */
    public function splitHand(Data $data, string $player, string $hand): void
    {
        $players = $this->getArray($data->get('players', []));
        $deck = $this->getArray($data->get('deck_of_cards', []));
        $rules = new Rules();
    
        if (!isset($players[$player]) || !is_array($players[$player]) || !isset($players[$player]['hands']) || !is_array($players[$player]['hands']) || !isset($players[$player]['hands'][$hand])) {
            return;
        }
    
        $handData = $this->getArray($players[$player]['hands'][$hand]);
        $currentCards = $this->getArray($handData['cards']);
    
        if (count($currentCards) < 2) {
            return;
        }
    
        [$rawCard1, $rawCard2] = array_slice($currentCards, 0, 2);
        $card1 = $this->getCard($rawCard1);
        $card2 = $this->getCard($rawCard2);
    
        $originalBet = isset($handData['bet']) && is_numeric($handData['bet']) ? (int) $handData['bet'] : 10;
    
        $newCard1 = $this->getCard(array_shift($deck));
        $newCard2 = $this->getCard(array_shift($deck));
    
        $players[$player]['hands'] = [
            'hand1' => $this->createHand([$card1, $newCard1], $originalBet, 'active', $rules),
            'hand2' => $this->createHand([$card2, $newCard2], $originalBet, 'waiting', $rules),
        ];
    
        $data->set('deck_of_cards', $deck);
        $data->set('players', $players);
        $data->set('active_player', $player);
        $data->set('active_hand', 'hand1');
        $data->save();
    }

    /**
     * Get array
     * 
     * @param mixed $input
     * @return array<mixed>
     */
    private function getArray(mixed $input): array
    {
        return is_array($input) ? $input : [];
    }

    /**
     * Ensure a valid card format.
     *
     * @param mixed $card
     * @return array{value: string, suit: string}
     */
    private function getCard(mixed $card): array
    {
        if (is_array($card) && isset($card['value'], $card['suit']) && is_string($card['value']) && is_string($card['suit'])) {
            $value = (string) $card['value'];
            $suit = (string) $card['suit'];
            $symbol = match ($suit) {
                'Hearts' => '♥', 'Diamonds' => '♦', 'Clubs' => '♣', 'Spades' => '♠', default => $suit
            };
            $color = in_array($suit, ['Hearts', 'Diamonds']) ? 'red' : 'black';
            return [
                'value' => $value,
                'suit' => $suit,
                'card' => "<span style='color:{$color}'>{$value}{$symbol}</span>"
            ];
        }
        return ['value' => '0', 'suit' => '', 'card' => '0'];
    }

    /**
     * Create a hand structure.
     *
     * @param array<int, array<string, mixed>> $cards
     * @param int $bet
     * @param string $status
     * @param Rules $rules
     * @return array<string, mixed>
     */
    private function createHand(array $cards, int $bet, string $status, Rules $rules): array
    {
        return [
            'cards' => $cards,
            'points' => $rules->countPoints($cards),
            'status' => $status,
            'bet' => $bet,
        ];
    }
}