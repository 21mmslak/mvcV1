<?php

namespace App\Controller;

use App\Card\DeckOfCards;
use App\BlackJack\BlackJack;
use App\BlackJack\BlackJackRules;
use App\BlackJack\BlackJackService;
use App\BlackJack\BlackJackSession;
use App\BlackJack\BlackJackRender;
use App\BlackJack\BlackJackGameManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Controller\CardController;
use LogicException;

use function PHPUnit\Framework\throwException;

class BlackJackController extends AbstractController
{
    private BlackJackRules $rules;
    // private BlackJackService $blackJackService;
    private BlackJackSession $state;
    private BlackJackRender $renderer;
    private BlackJackGameManager $gameManager;


    public function __construct(
        BlackJackRules $rules,
        // BlackJackService $blackJackService,
        BlackJackSession $state,
        BlackJackRender $renderer,
        BlackJackGameManager $gameManager
    ) {
        $this->rules = $rules;
        // $this->blackJackService = $blackJackService;
        $this->state = $state;
        $this->renderer = $renderer;
        $this->gameManager = $gameManager;
    }

    #[Route('/doc', name: 'doc')]
    public function doc(): Response
    {
        return $this->render('black_jack/doc.html.twig');
    }

    #[Route('/roule', name: 'roule')]
    public function roule(): Response
    {
        return $this->render('black_jack/roule.html.twig');
    }

    #[Route('/game_start', name: 'game_start')]
    public function gameStart(SessionInterface $session): Response
    {
        if (!$session->has('coins')) {
            $this->state->setCoins($session, 100);
        }

        if (!$session->get("game_started", false)) {
            $result = $this->gameManager->startNewGame($session);
            $session->set("game_started", true);

            if ($result['isOver']) {
                $winner = $this->rules->decideWinner(
                    $result['dealerPoints'],
                    $result['playerPoints'],
                    $session
                );

                $this->gameManager->resetGame($session);

                return $this->renderer->renderWinner(
                    $winner,
                    $result['dealerCards'],
                    $result['playerCards'],
                    $result['dealerPoints'],
                    $result['playerPoints'],
                    $session
                );
            }

            $split = $result['playerCards'][0]['value'] === $result['playerCards'][1]['value'];

            return $this->renderer->renderGameStart(
                $result['playerCards'],
                $result['dealerCards'],
                $result['dealerPoints'],
                $result['playerPoints'],
                $split,
                $session
            );
        }

        return $this->renderer->renderGameStart(
            $this->state->getPlayerCards($session),
            $this->state->getDealerCards($session),
            $this->state->getPoints($session, "dealer_points"),
            $this->state->getPoints($session, "player_points"),
            (bool) $this->state->get($session, "is_split", false),
            $session
        );
    }

    #[Route('/add_card', name: 'add_card')]
    public function addCard(SessionInterface $session): Response
    {
        $result = $this->gameManager->addCardToPlayer($session);

        if ($result['isOver']) {
            $this->gameManager->resetGame($session);
            $winner = $this->rules->decideWinner(
                $result['dealerPoints'],
                $result['playerPoints'],
                $session
            );

            return $this->renderer->renderWinner(
                $winner,
                $result['dealerCards'],
                $result['playerCards'],
                $result['dealerPoints'],
                $result['playerPoints'],
                $session
            );
        }

        return $this->renderer->renderGameStart(
            $result['playerCards'],
            $result['dealerCards'],
            $result['dealerPoints'],
            $result['playerPoints'],
            (bool) $this->state->get($session, "is_split", false),
            $session
        );
    }

    #[Route('/add_card_split', name: 'add_card_split')]
    public function addCardSplit(SessionInterface $session): Response
    {
        $result = $this->gameManager->addCardToSplitHand($session);

        if ($result['shouldRedirect']) {
            return $this->redirectToRoute("stand_split");
        }

        return $this->renderer->renderGameSplit(
            $result['dealerCards'],
            $result['dealerPoints'],
            $result['hand1'],
            $result['hand2'],
            $result['playerPoints1'],
            $result['playerPoints2'],
            $result['activeHand'],
            $session
        );
    }

    #[Route('/stand', name: 'stand')]
    public function stand(SessionInterface $session): Response
    {
        $result = $this->gameManager->stand($session);

        return $this->renderer->renderWinner(
            $result['winner'],
            $result['dealerCards'],
            $result['playerCards'],
            $result['dealerPoints'],
            $result['playerPoints'],
            $session
        );
    }

    #[Route('/stand_split', name: 'stand_split')]
    public function standSplit(SessionInterface $session): Response
    {
        $result = $this->gameManager->standSplit($session);

        if ($result === null) {
            return $this->renderer->renderGameSplit(
                $this->state->getDealerCards($session),
                $this->state->getPoints($session, "dealer_points"),
                $this->state->getHand($session, "hand1"),
                $this->state->getHand($session, "hand2"),
                $this->state->getPoints($session, "player_points_1"),
                $this->state->getPoints($session, "player_points_2"),
                "hand2",
                $session
            );
        }

        return $this->renderer->renderWinnerSplit(
            $result['dealerCards'],
            $result['dealerPoints'],
            $result['hand1'],
            $result['hand2'],
            $result['points1'],
            $result['points2'],
            $result['winner1'],
            $result['winner2'],
            $session
        );
    }

    #[Route('/split', name: 'split')]
    public function split(SessionInterface $session): Response
    {
        $result = $this->gameManager->split($session);

        return $this->renderer->renderGameSplit(
            $result['dealerCards'],
            $result['dealerPoints'],
            $result['hand1'],
            $result['hand2'],
            $result['points1'],
            $result['points2'],
            $result['active'],
            $session
        );
    }
}