<?php

// src/Controller/HomeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{

    #[Route('/home', methods: ['GET'], name: 'home')]
    function showHomepage()
    {

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($user->getLicenseKey() !== null) {
        } else {
            return $this->render(
                'base.html.twig',
                [
                    'subview' => 'views/home/noMembership.html.twig',
                    'user' => $user,
                    'showToast' => false,
                    'toastClass' => 'bg-primary',
                    'toastTitle' => '',
                    'toastBody' => '',
                    'site_name' => $this->getParameter('site_name'),
                ]
            );
        }
    }
}
