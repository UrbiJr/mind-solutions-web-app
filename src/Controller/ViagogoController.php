<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ViagogoController extends AbstractController
{
    #[Route('/viagogo', name: 'viagogo_connection_show')]
    public function index(): Response
    {
        return $this->render('viagogo/connection.html.twig');
    }

}
