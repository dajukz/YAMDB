<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Form\SearchType;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;

class MainController extends AbstractController
{
    /**
     * Direct call to TMDB API that returns the data to the homepage view
     *
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route('/', name: 'home_page', methods: ['GET'])]
    public function homePage(): Response
    {
        $client = new Client();
        $cache = new FilesystemAdapter();
        $form = $this->createForm(SearchType::class);

        $page = 'page1';

        // caching response for increased performance/reduced TMDB calls
        $response = $cache->get($page, function (ItemInterface $item) use ($client) {
            echo '<span>MISS</span>';

            // expires after 30 minutes
            $item->expiresAfter(1800);

            try {
                // get first page of most popular movies
                $response = $client->request('GET', 'https://api.themoviedb.org/3/movie/popular?language=en-US&page=1', [
                    'headers' => [
                        'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIxNmNjZDFiMjllYTJlZmE5ZjI4ZGMxNzI4ZTUzMTk4YiIsInN1YiI6IjY2MWZlOGQ0MjE2MjFiMDE2NGYxMDVjYyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.SY-1BPu3v5sMTOd4GQ6BPckYXMzG5P45RR5l8d9XApA',
                        'accept' => 'application/json',
                    ],
                ]);

                $response = json_decode($response->getBody())->results;

                // search the crew for each movie in response
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
            'form' => $form
        ]);
    }

    /**
     * Test function to check if the db connection works
     *
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('test', name: 'test_movies')]
    public function test(EntityManagerInterface $entityManager): Response
    {
        $response = $entityManager->getRepository(Movie::class)->findAll();
        $form = $this->createForm(SearchType::class);

        return $this->render('main/homepage.html.twig', [
            'movies' => $response,
            'form' => $form
        ]);
    }
}
