<?php

namespace App\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;

class MainController extends AbstractController
{
    #[Route('/', name: 'home_page', methods: ['GET'])]
    public function homePage(): Response
    {
        $client = new Client();
        $cache = new FilesystemAdapter();

        $page = 'page1';

        // caching response for increased performance/reduced TMDB calls
        $response = $cache->get($page, function (ItemInterface $item) use ($client) {
            echo '<span>MISS</span>';

            // expires after 30 minutes
            $item->expiresAfter(1800);

            try {
                $response = $client->request('GET', 'https://api.themoviedb.org/3/movie/popular?language=en-US&page=1', [
                    'headers' => [
                        'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIxNmNjZDFiMjllYTJlZmE5ZjI4ZGMxNzI4ZTUzMTk4YiIsInN1YiI6IjY2MWZlOGQ0MjE2MjFiMDE2NGYxMDVjYyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.SY-1BPu3v5sMTOd4GQ6BPckYXMzG5P45RR5l8d9XApA',
                        'accept' => 'application/json',
                    ],
                ]);

                $response = json_decode($response->getBody())->results;

                foreach ($response as $movie) {
                    $id = $movie->id;

                    $tempResponse = $client->request('GET', 'https://api.themoviedb.org/3/movie/'.$id.'/credits?language=en-US', [
                        'headers' => [
                            'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIxNmNjZDFiMjllYTJlZmE5ZjI4ZGMxNzI4ZTUzMTk4YiIsInN1YiI6IjY2MWZlOGQ0MjE2MjFiMDE2NGYxMDVjYyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.SY-1BPu3v5sMTOd4GQ6BPckYXMzG5P45RR5l8d9XApA',
                            'accept' => 'application/json',
                        ],
                    ]);

                    $tempResponse = json_decode($tempResponse->getBody())->crew;

                    // Search for the name of the director of a specific movie
                    foreach ($tempResponse as $crewMember) {
                        $movie->release_date = substr($movie->release_date, 0, 4);
                        if ('Director' === $crewMember->job) {
                            $movie->director = $crewMember->name;
                        }
                    }
                }
            } catch (GuzzleException $e) {
                return new Response('Query failed: '.$e, 500);
            }

            return $response;
        });

        return $this->render('main/homepage.html.twig', [
            'movies' => $response,
        ]);
    }
}
