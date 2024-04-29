<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Form\SearchType;
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
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     * @throws InvalidArgumentException
     */
    #[Route('/', name: 'home_page', methods: ['GET'])]
    public function homePage(
        EntityManagerInterface $entityManager,
        Request $request,
        PaginatorInterface $paginator
    ): Response
    {
        return $this->getHomepageView($request, $entityManager, $paginator);
    }

    /**
     * Renders the detailed page of a specific movie with a given ID
     *
     * @param int $id
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/movie/{id}', name: 'movie_detailed')]
    public function showById(int $id, EntityManagerInterface $entityManager): Response
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


    /**
     * runs search and renders results
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param string $term
     * @return Response
     */
    #[Route('/search', name: 'search_movies')]
    public function search(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the search term from the request
            $searchTerm = $request->request->all();
//            dd($searchTerm['search']);
            // Redirect to the route that displays the search results
            return $this->redirectToRoute('search_results', $searchTerm);
        }
        try {
            return $this->getHomepageView($request, $entityManager, $paginator);

        } catch (InvalidArgumentException $e) {

            return new Response('Query failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * return a view of the search results queried from search form
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param PaginatorInterface $paginator
     * @return Response
     */
    #[Route('/search-results', name: 'search_results')]
    public function searchResults(Request $request, EntityManagerInterface$entityManager, PaginatorInterface $paginator): Response
    {

        $cache = new FilesystemAdapter();
        $form = $this->createForm(SearchType::class);

        $searchTerm = $request->query->all()['search']['query'];

        // dd($searchTerm['search']['query']); //access searchterm
        // Perform search operation using the $searchTerm
        try {
            $response = $cache->get($searchTerm, function (ItemInterface $item) use ($entityManager, $searchTerm, $paginator) {

                //set expiry to 30min
                $item->expiresAfter(1800);
                $query = $entityManager->getRepository(Movie::class)->getMoviesWithTermQueryBuilder($searchTerm);

                //paginate with query
                return $paginator->paginate(
                    $query,
                    1,
                    10
                );
            });
        } catch (Exception|ORMException|InvalidArgumentException $exception) {
            return new Response('Query failed: ' . $exception->getMessage(), 500);

        }

        return $this->render('main/homepage.html.twig', [
            'pagination' => $response,
            'form' => $form
        ]);
    }

    /**
     * returns a twig render response depending on the list of movies that need to be rendered
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param PaginatorInterface $paginator
     * @return Response
     * @throws InvalidArgumentException
     */
    public function getHomepageView(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
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

            } catch (Exception|ORMException|InvalidArgumentException $e) {
                return new Response('Query failed: ' . $e->getMessage(), 500);
            }

            return $pagination;
        });

        return $this->render('main/homepage.html.twig', [
            'pagination' => $pagination,
            'form' => $form
        ]);
    }
}
