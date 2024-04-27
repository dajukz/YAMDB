<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Form\SearchType;
use App\Repository\MovieRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use GuzzleHttp\Client;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;

class MainController extends AbstractController
{
    /**
     * Direct call to TMDB API that returns the data to the homepage view
     *
     * @param $entityManager
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route('/', name: 'home_page', methods: ['GET'])]
    public function homePage(EntityManagerInterface $entityManager, MovieRepository $movieRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $cache = new FilesystemAdapter();
        $form = $this->createForm(SearchType::class);

        // cacheKey is also the page number for paginator
        $cacheKey = $request->query->getInt('page', 1);

        // caching response for increased performance/reduced TMDB calls
        $pagination = $cache->get('page' . $cacheKey, function (ItemInterface $item) use ($entityManager, $paginator, $cacheKey) {

            // expires after 30 minutes
            $item->expiresAfter(1800);

            try {

                //Query the movies from database
                $response = $entityManager->getRepository(Movie::class)->getWithQueryBuilder();
                $pagination = $paginator->paginate(
                    $response,
                    $cacheKey,
                    10
                );
                //TODO: check of caching mogelijk is per page (van 10)

            } catch (Exception | ORMException $e) {
                return new Response('Query failed: '.$e->getMessage(), 500);
            }

            return $pagination;
        });

        return $this->render('main/homepage.html.twig', [
            'pagination' => $pagination,
            'form' => $form
        ]);
    }

    /**
     * Test function to check if the db connection works
     *
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/test', name: 'test_movies')]
    public function test(EntityManagerInterface $entityManager): Response
    {
        $response = $entityManager->getRepository(Movie::class)->findAll();
        $form = $this->createForm(SearchType::class);

        return $this->render('main/homepage.html.twig', [
            'movies' => $response,
            'form' => $form
        ]);
    }

    #[Route('/movie/{id}', name: 'movie_detailed')]
    public function showById(int $id)
    {
        return new Response('<h1>TEST' . $id . '</h1>');
    }
}
