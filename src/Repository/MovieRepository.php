<?php

namespace App\Repository;

use App\Entity\Movie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends ServiceEntityRepository<Movie>
 *
 * @method Movie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Movie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Movie[]    findAll()
 * @method Movie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
    }


    /**
     * Querybuilder that fetches all movies from db
     * 
     * @return QueryBuilder
     */
    public function getWithQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('movie')
            ->orderBy('movie.id');
    }

    /**
     *  fetch a single movie with the given ID
     *
     * @param int $id
     * @return Movie|Response
     */
    public function getMovieById(int $id): Movie|Response
    {
        $response = $this->find($id);

        if ($response === null) return new Response('Movie with ID ' . $id . 'not found', 404);

        return $response;
    }
}
