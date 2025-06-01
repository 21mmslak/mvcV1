<?php

namespace App\Project;

use App\Entity\Scoreboard;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;

class StartBlackJack
{
    private Security $security;
    private EntityManagerInterface $em;

    public function __construct(Security $security, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->em = $em;
    }

    public function start(Data $data): void
    {
        $coins = $this->initializePlayerCoins($data);
        $data->set('coins', $coins);
        $data->set('game_started', true);

        $deck = (new StartDeck())->startGame();
        $dealerCards = $this->initializeDealer($deck);
        $players = $this->initializePlayers($data);

        $rules = new Rules();
        $dealerPointsStart = $rules->countPoints([$dealerCards['card_one']]);
        $dealerPoints = $rules->countPoints([$dealerCards['card_one'], $dealerCards['card_two']]);

        $data->set('players', $players);
        $data->set('active_player', 'player1');
        $data->set('active_hand', 'hand1');
        $data->set('dealer_card_one', [$dealerCards['card_one']]);
        $data->set('dealer_card_two', [$dealerCards['card_two']]);
        $data->set('dealer_points_start', $dealerPointsStart);
        $data->set('dealer_points', $dealerPoints);
        $data->set('dealer_cards', []);
        $data->set('deck_of_cards', $dealerCards['remaining_deck']);

        $data->save();
    }

    private function initializePlayerCoins(Data $data): int
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $repo = $this->em->getRepository(Scoreboard::class);
            $scoreboard = $repo->findOneBy(['user' => $user]);
    
            if (!$scoreboard) {
                $scoreboard = new Scoreboard();
                $scoreboard->setUser($user)->setCoins(5000);
                $this->em->persist($scoreboard);
                $this->em->flush();
            }
    
            return (int) $scoreboard->getCoins();
        }
    
        $coins = $data->get('coins');
        return is_numeric($coins) ? (int) $coins : 5000;
    }

    /**
     * Gives card to dealer
     *
     * @param array<int, array<string, mixed>> $deck Array of cards
     * @return array{card_one: array<string, mixed>, card_two: array<string, mixed>, remaining_deck: array<int, array<string, mixed>>}
     */
    private function initializeDealer(array $deck): array
    {
        $cardOne = array_shift($deck);
        $cardTwo = array_shift($deck);

        $cardOne = is_array($cardOne) ? $cardOne : [];
        $cardTwo = is_array($cardTwo) ? $cardTwo : [];

        return [
            'card_one' => $cardOne,
            'card_two' => $cardTwo,
            'remaining_deck' => $deck,
        ];
    }

    /**
     * Initialize players with default hands.
     *
     * @param Data $data
     * @return array<string, array{hands: array<string, array{cards: array, points: int, status: string, bet: ?int}>}>
     */
    private function initializePlayers(Data $data): array
    {
        $existingPlayers = $data->get('players', ['player1' => []]);
        if (!is_array($existingPlayers)) {
            $existingPlayers = ['player1' => []];
        }

        $playerCount = count($existingPlayers);
        $players = [];

        for ($i = 1; $i <= $playerCount; $i++) {
            $players["player{$i}"] = [
                'hands' => [
                    'hand1' => [
                        'cards' => [],
                        'points' => 0,
                        'status' => 'waiting',
                        'bet' => null
                    ]
                ]
            ];
        }

        return $players;
    }
}