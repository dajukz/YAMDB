<?php

namespace App\DataFixtures;

use App\Entity\Movie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

class AppFixtures extends Fixture
{
    private LoggerInterface $logger;
    private string $TMDB_API_KEY;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->TMDB_API_KEY = $_ENV['TMDB_TOKEN'];
    }

    public function load(ObjectManager $manager): void
    {
        $client= new Client();

        try {
            //TODO: make iterator with delay to prevent rate limiting for full db load
            $response = $this->getMovieList($client, 1);
            $response = json_decode($response->getBody())->results;

            foreach ($response as $jsonObj) {

                $id = $jsonObj->id;
                $tempResponse = $this->getMovieCrewById($client, $id);
                $tempResponse = json_decode($tempResponse->getBody())->crew;

                // Search for the name of the director of a specific movie
                foreach ($tempResponse as $crewMember) {
                    $jsonObj->release_date = substr($jsonObj->release_date, 0, 4);
                    if ('Director' === $crewMember->job) {
                        $jsonObj->director = $crewMember->name;

                        $movie = new Movie();

                        //insert movie into db after director name has been found
                        $movie->setTitle($jsonObj->title);
                        $movie->setDirector($jsonObj->director);
                        $movie->setReleaseDate($jsonObj->release_date);
                        $movie->setOverview($jsonObj->overview);

                        $manager->persist($movie);
                    }

                }
            }
        } catch (GuzzleException $e) {
            $this->logger->error('Synchronization with database failed: \n' . $e->getMessage());
        }

        $manager->flush();
    }

    /**
     * With the generated client and pagenumber returns a specific list of movies
     *
     * @return Response
     * @param Client $client
     * @param int $page
     *
     * @throws GuzzleException
     */
    private function getMovieList(Client $client, int $page): ?Response {
        return $client->request('GET', 'https://api.themoviedb.org/3/movie/popular?language=en-US&page=1', [
            'headers' => [
                'Authorization' => $this->TMDB_API_KEY,
                'accept' => 'application/json',
            ],
        ]);
    }

    /**
     * With the generated client and movie ID returns a specific movie crew
     *
     * @return Response
     * @param Client $client
     * @param int $id
     *
     * @throws GuzzleException
     */
    private function getMovieCrewById($client, int $id): ?Response {
        return $client->request('GET', 'https://api.themoviedb.org/3/movie/' . $id . '/credits?language=en-US', [
            'headers' => [
                'Authorization' => $this->TMDB_API_KEY,
                'accept' => 'application/json',
            ],
        ]);
    }
}
