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
        $game = $data->get('game_started');
        if ($game) return;

        $dealerPoints = $data->get('dealer_points');
        $players = $data->get('players');

        $user = $this->security->getUser();
        $coins = $data->get('coins');

        if ($user instanceof User) {
            $scoreboard = $this->em->getRepository(Scoreboard::class)->findOneBy(['user' => $user]);
            if ($scoreboard) {
                $coins = $scoreboard->getCoins();
            }
        }

        foreach ($players as $name => &$player) {
            foreach ($player['hands'] as $handName => &$hand) {
                $playerPoints = $hand['points'];
                $bet = $hand['bet'] ?? 10;

                if ($playerPoints > 21) {
                    $coins -= $bet;
                    $hand['result'] = "Dealer wins against {$name} {$handName} (bust)";
                } elseif ($dealerPoints > 21) {
                    $coins += $bet;
                    $hand['result'] = "{$name} {$handName} wins! Dealer busts.";
                } elseif ($playerPoints > $dealerPoints) {
                    $coins += $bet;
                    $hand['result'] = "{$name} {$handName} wins with higher points!";
                } elseif ($dealerPoints > $playerPoints) {
                    $coins -= $bet;
                    $hand['result'] = "Dealer wins against {$name} {$handName}";
                } else {
                    $hand['result'] = "{$name} {$handName} and dealer draw.";
                }
            }
        }

        if ($user instanceof User && isset($scoreboard)) {
            $scoreboard->setCoins($coins);
            $this->em->persist($scoreboard);
            $this->em->flush();
        } else {
            $data->set('coins', $coins);
        }

        $data->set('players', $players);
        $data->save();
    }



    // public function decideWinner(Data $data): void
    // {
    //     $game = $data->get('game_started');
    //     if ($game) return;

    //     $dealerPoints = $data->get('dealer_points');
    //     $coins = $data->get('coins');
    //     $players = $data->get('players');

    //     foreach ($players as $name => &$player) {
    //         foreach ($player['hands'] as $handName => &$hand) {
    //             $playerPoints = $hand['points'];
    //             $bet = $hand['bet'] ?? 10;

    //             if ($playerPoints > 21) {
    //                 $coins -= $bet;
    //                 $hand['result'] = "Dealer wins against {$name} {$handName} (bust)";
    //             } elseif ($dealerPoints > 21) {
    //                 $coins += $bet;
    //                 $hand['result'] = "{$name} {$handName} wins! Dealer busts.";
    //             } elseif ($playerPoints > $dealerPoints) {
    //                 $coins += $bet;
    //                 $hand['result'] = "{$name} {$handName} wins with higher points!";
    //             } elseif ($dealerPoints > $playerPoints) {
    //                 $coins -= $bet;
    //                 $hand['result'] = "Dealer wins against {$name} {$handName}";
    //             } else {
    //                 $hand['result'] = "{$name} {$handName} and dealer draw.";
    //             }
    //         }
    //     }

    //     $data->set('coins', $coins);
    //     $data->set('players', $players);
    //     $data->save();
    // }

    // public function decideWinnerSplit(Data $data): void
    // {
    //     $game = $data->get('game_started');
    //     if ($game) {
    //         return;
    //     }

    //     $dealerPoints = $data->get('dealer_points');
    //     $coins = $data->get('coins');
    //     $results = [];

    //     foreach (['hand1', 'hand2'] as $hand) {
    //         $playerPoints = $data->get("{$hand}Points", 0);

    //         if ($playerPoints > 21) {
    //             $coins -= 10;
    //             $results[] = "Dealer wins against {$hand} (Player busts)";
    //         } elseif ($dealerPoints > 21) {
    //             $coins += 10;
    //             $results[] = "Player wins with {$hand} (Dealer busts)";
    //         } elseif ($playerPoints > $dealerPoints) {
    //             $coins += 10;
    //             $results[] = "Player wins with {$hand}!";
    //         } elseif ($dealerPoints > $playerPoints) {
    //             $coins -= 10;
    //             $results[] = "Dealer wins against {$hand}!";
    //         } else {
    //             $results[] = "Draw with {$hand}!";
    //         }
    //     }

    //     $data->set('coins', $coins);
    //     $data->set('results', $results);
    //     $data->save();
    // }
}