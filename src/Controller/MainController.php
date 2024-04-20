<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController
{
    #[Route('/')]
    public function homePage()
    {
        $rand = rand(0, 10);

        return new Response(var_dump($rand));
    }
}
