<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\User\UserRegistration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/register', name: 'register')]
    public function register(): Response
    {
        return $this->render('user/register.html.twig', []);
    }

    #[Route('/register_account', name: 'register_account')]
    public function register_account(Request $request, UserRegistration $registrationService): Response
    {
        $result = $registrationService->register($request);

        return $this->render('user/index.html.twig', [
            'message' => $result['message'],
            'success' => $result['success'],
        ]);
    }

    #[Route('/user', name: 'app_user')]
    public function userHome(): Response
    {
        return $this->render('user/index.html.twig', []);
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, SessionInterface $session): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
    
        $session->clear();

        return $this->render('user/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }
    
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Symfony logout
    }
}