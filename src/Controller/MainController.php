<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Form\SearchType;
use App\Repository\MovieRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
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
    public function homePage(
        EntityManagerInterface $entityManager,
        MovieRepository $movieRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response
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

                //create query that queries the movies from database
                $query = $entityManager->getRepository(Movie::class)->getWithQueryBuilder();

                //paginate with query
                $pagination = $paginator->paginate(
                    $query,
                    $cacheKey,
                    10
                );
                //TODO: check of caching mogelijk is per page (van 10)

            } catch (Exception | ORMException | InvalidArgumentException $e) {
                return new Response('Query failed: '.$e->getMessage(), 500);
            }

            return $pagination;
        });

        return $this->render('main/homepage.html.twig', [
            'pagination' => $pagination,
            'form' => $form
        ]);
    }

    #[Route('/movie/{id}', name: 'movie_detailed')]
    public function showById(int $id, EntityManagerInterface $entityManager)
    {
        $cache = new FilesystemAdapter();

        try {
            $response = $cache->get($id, function (ItemInterface $item) use ($entityManager, $id) {

                //set expiry to 30min
                $item->expiresAfter(1800);

                //return movie with the specific id
                return $entityManager->getRepository(Movie::class)->getMovieById($id);
            });

            return $this->render('main/detail_page.html.twig', [
                'movie' => $response,
            ]);

        } catch (Exception | ORMException | InvalidArgumentException $exception) {

            return new Response('Query failed: '.$exception->getMessage(), 500);
        }
    }
}
