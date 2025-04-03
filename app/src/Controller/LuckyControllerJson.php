<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class LuckyControllerJson extends AbstractController
{
    #[Route("/api/lucky/number")]
    public function jsonNumber(): Response
    {
        $number = random_int(0, 100);

        $data = [
            'lucky-number' => $number,
            'lucky-message' => 'Hi there!',
        ];

        // $response = new Response();
        // $response->setContent(json_encode($data));
        // $response->headers->set('Content-Type', 'application/json');

        // return $response;

        return new JsonResponse($data);
    }

    #[Route("/api/quote")]
    public function jsonQuote(): Response
    {
        $quotes = [
            'Om jag har sett längre än andra, så är det för att jag stått på jättars axlar.',
            'Jag kan beräkna himlakropparnas rörelser, men inte människors vansinne.',
            'Vad vi vet är en droppe, vad vi inte vet är ett hav.'
            ];

        $number = random_int(0, 2);


        $data = [
            'quote' => $quotes[$number]
        ];

        return new JsonResponse($data);
    }


    #[Route("/api")]
    public function jsonAll(RouterInterface $router): Response
    {
        $routes = $router->getRouteCollection();
        $apiRoutes = [];

        foreach ($routes as $name => $route) {
            $path = $route->getPath();

            if (str_starts_with($path, '/api/') && $path !== '/api') {
                $apiRoutes[] = [
                    'name' => $name,
                    'path' => $path,
                ];
            }
        }

        return $this->render('api.html.twig', [
            'api_routes' => $apiRoutes,
        ]);
    }

}