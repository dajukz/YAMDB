<?php

namespace App\DataFixtures;

use App\Entity\Movie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

class AppFixtures extends Fixture
{
    private LoggerInterface $logger;
    private Connection $con;
    private string $TMDB_API_KEY;
    private int $MAX_ITERATOR;

    public function __construct(LoggerInterface $logger, Connection $con)
    {
        $this->logger = $logger;
        $this->TMDB_API_KEY = $_ENV['TMDB_TOKEN'];
        $this->MAX_ITERATOR = 500;
        //TODO: connection $_ENV['FIXTURE_URL'] laten gebruiken
        $this->con = $con;

    }

    public function load(ObjectManager $manager): void
    {
        $client= new Client();

        try {

            // 5 iterations+ for max 500 pages of 20 movies added
            for ($i = 0; $i <= 5; $i++) {

                $this->executeFixtureBlock($manager, $client, $i);
                $manager->flush();
                //pause after each iteration of 100 pages
                sleep(60);
            }

        } catch (GuzzleException $e) {
            $this->logger->error('Synchronization with database failed: \n' . $e->getMessage());
        }

        $manager->flush();

        try {

            $this->removeAllDuplicates();

        } catch (Exception $exception) {

            $this->logger->critical('Duplicates not removed: ' . $exception);

        }
    }

    /**
     * With the generated client and pagenumber returns a specific list of movies
     *
     * @param Client $client
     * @param int $page
     *
     * @return Response|null
     * @throws GuzzleException
     */
    private function getMovieList(Client $client, int $page): ?Response {
        return $client->request('GET', 'https://api.themoviedb.org/3/movie/popular?language=en-US&page=' . $page, [
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

    /**
     * Executes fixture per 100 pages, hard limited on 500 as max page to comply with TMDB API limit
     * @param ObjectManager $manager
     * @param Client $client
     * @param int $iteratorStart
     * @return void
     * @throws GuzzleException
     */
    private function executeFixtureBlock(ObjectManager $manager, Client $client, int $iteratorStart=0): void
    {

        $iteratorStart=$iteratorStart*100;

        // if the page is over 500 the API will not accept the call
        if (($iteratorStart+100) > $this->MAX_ITERATOR) return;

        // fetches movies per 100 pages max
        for ($i = $iteratorStart; $i < ($iteratorStart+100); $i++) {
            $response = $this->getMovieList($client, $i+1);
            $response = json_decode($response->getBody())->results;

            sleep(1);
            $this->logger->info($i . ' Iterations done out of ' . $this->MAX_ITERATOR);

            foreach ($response as $jsonObj) {

                $id = $jsonObj->id;
                $tempResponse = $this->getMovieCrewById($client, $id);
                $tempResponse = json_decode($tempResponse->getBody())->crew;

                // Search for the name of the director of a specific movie
                foreach ($tempResponse as $crewMember) {

                    $jsonObj->release_date = intval(substr($jsonObj->release_date, 0, 4));
                    if ('Director' === $crewMember->job) {

                        $jsonObj->director = $crewMember->name;

                        $movie = new Movie();

                        //insert movie into db after director name has been found
                        $movie->setTitle($jsonObj->title);
                        $movie->setDirector($jsonObj->director);
                        $movie->setReleaseDate($jsonObj->release_date);
                        $movie->setOverview($jsonObj->overview);

                        $manager->persist($movie);
                        $this->logger->info('persisted: ' . $movie->getTitle());
                    }

                }
            }
        }
    }

    /**
     * Raw querystring to remove all duplicates from DB after API calls
     * @return void
     * @throws Exception
     */
    private function removeAllDuplicates(): void {

        $queryString = "DELETE t1 FROM movie t1
            INNER JOIN movie t2 
            WHERE 
            t1.id < t2.id AND 
            t1.title = t2.title;";
        $statement = $this->con->prepare($queryString);
        $statement->executeStatement();

        $this->logger->info('Duplicates removed');
    }
}
