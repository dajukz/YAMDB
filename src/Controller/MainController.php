<?php

namespace App\Controller;

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

        return $this->render('main/homepage.html.twig', [
            'movies' => $movies,
        ]);
    }
}
