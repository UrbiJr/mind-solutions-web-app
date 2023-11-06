<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ErrorController extends AbstractController
{

    #[Route('/error/404', name: 'error_404')]
    public function error404(): Response
    {
        return $this->render(
            'error.html.twig',
            [
                'subview' => 'views/error/error404.html.twig',
            ]
        );
    }

    #[Route('/error/500', name: 'error_500')]
    public function error500(): Response
    {
        return $this->render(
            'error.html.twig',
            [
                'subview' => 'views/error/error500.html.twig',
            ]
        );
    }

    #[Route('/error/maintenance', name: 'error_maintenance')]
    public function maintenance(): Response
    {
        return $this->render(
            'error.html.twig',
            [
                'subview' => 'views/error/maintenance.html.twig',
            ]
        );
    }
}
