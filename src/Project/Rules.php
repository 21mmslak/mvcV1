<?php

namespace App\Project;

class Rules
{
    /**
     * Calculates the point value of a hand of cards.
     *
     * @param array<int, array<string, mixed>> $hand Array of cards
     * @return int Total points of the hand
     */
    public function countPoints(array $hand): int
    {
        $points = 0;
        $aces = 0;

        foreach ($hand as $card) {
            if (!isset($card['value'])) {
                continue;
            }

            $value = $card['value'];

            if (in_array($value, ['J', 'Q', 'K'], true)) {
                $points += 10;
            } elseif ($value === 'A') {
                $aces++;
                $points += 11;
            } elseif (is_numeric($value)) {
                $points += (int)$value;
            }
        }

        while ($points > 21 && $aces > 0) {
            $points -= 10;
            $aces--;
        }

        return $points;
    }
}