<?php

// src/Controller/DashboardController.php
namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

class DashboardController extends AbstractController
{

    #[Route('/dashboard', methods: ['GET'], name: 'dashboard')]
    #[IsGranted('ROLE_MEMBER')]
    function showDashboard(#[CurrentUser] ?User $user)
    {
        return $this->render(
            'base.html.twig',
            [
                'subview' => 'views/dashboard/noMembership.html.twig',
                'user' => $user,
                'bannerTitle' => new TranslatableMessage('This is your dashboard overview'),
                'bannerSubtitle' => new TranslatableMessage('Have a look at your analytics. Have you hopped on our Discord yet?'),
                'bannerBtnAction' => "https://discord.gg/4S4uzSrys7",
                'bannerBtnIcon' => '<i class="fa-brands fa-discord"></i>',
                'bannerBtnText' => new TranslatableMessage('Join Discord'),
                'bannerBtnId' => "",
                'bannerBtnTarget' => '',
                'displayBanner' => true,
                'showToast' => false,
                'toastClass' => 'bg-primary',
                'toastTitle' => '',
                'toastBody' => '',
                'site_name' => $this->getParameter('site_name'),
            ]
        );
    }

    #[Route('/dashboard', methods: ['GET'], name: 'dashboard')]
    function showNoMembership(#[CurrentUser] ?User $user,)
    {
        $whopClientId = $this->getParameter('whop_client_id');
        $whopRedirectUri = $this->getParameter('whop_redirect_uri');

        return $this->render(
            'base.html.twig',
            [
                'subview' => 'views/dashboard/noMembership.html.twig',
                'user' => $user,
                'bannerTitle' => new TranslatableMessage('join_title', ['siteName' => $this->getParameter('site_name')]),
                'bannerSubtitle' => new TranslatableMessage("Looks like you don't have an active plan yet. Join today with the best offer ever!"),
                'bannerBtnAction' => "https://discord.gg/4S4uzSrys7",
                'bannerBtnIcon' => '<i class="fa-solid fa-key"></i>',
                'bannerBtnText' => new TranslatableMessage('Already have a pass?'),
                'bannerBtnId' => 'activateMembership',
                'bannerBtnTarget' => '',
                'displayBanner' => true,
                'showToast' => false,
                'toastClass' => 'bg-primary',
                'toastTitle' => '',
                'toastBody' => '',
                'site_name' => $this->getParameter('site_name'),
                'whopClientId' => $whopClientId,
                'whopRedirectUri' => $whopRedirectUri,
            ]
        );
    }
}
