<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReleasesController extends AbstractController
{
    #[Route('/releases', name: 'releases_show')]
    public function index(): Response
    {
        return $this->render('releases/releases.html.twig');
    }

}
