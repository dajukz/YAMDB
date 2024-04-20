<?php

namespace App\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'home_page')]
    public function homePage(): Response
    {
        $movieName = 'star wars';
        $movieName2 = 'dumb and dumber too';
        $movies = [$movieName, $movieName2];

        $client = new Client();
        try {
            $response = $client->request('GET', 'https://api.themoviedb.org/3/movie/popular?language=en-US&page=1', [
                'headers' => [
                    'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIxNmNjZDFiMjllYTJlZmE5ZjI4ZGMxNzI4ZTUzMTk4YiIsInN1YiI6IjY2MWZlOGQ0MjE2MjFiMDE2NGYxMDVjYyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.SY-1BPu3v5sMTOd4GQ6BPckYXMzG5P45RR5l8d9XApA',
                    'accept' => 'application/json',
                ],
            ]);


            //return new Response($response->getBody().results, 200);
        } catch (GuzzleException $e) {
            return new Response('Query failed: ' . $e, 500);
        }

        return $this->render('main/homepage.html.twig', [
            'movies' => json_decode($response->getBody())->results,
        ]);
    }
}
