<?php

// src/Controller/DashboardController.php
namespace App\Controller;

use App\Entity\User;
use App\Service\InventoryService;
use App\Service\Utils;
use App\Service\ViagogoAnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Exception;

class DashboardController extends AbstractController
{

    public function __construct(
        private readonly MemcachedAdapter $cache,
        private readonly Utils $utils,
        private readonly InventoryService $inventoryService,
        private readonly ViagogoAnalyticsService $viagogoAnalyticsService
    ) {
    }

    #[Route('/dashboard', methods: ['GET'], name: 'dashboard')]
    function showDashboard(#[CurrentUser] ?User $user)
    {
        if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
            return $this->redirectToRoute('dashboard_no_membership');
        }

        $whopClientId = $this->getParameter('whop_client_id');
        $whopRedirectUri = $this->getParameter('whop_redirect_uri');

        /* fetch analytics data */
        $currency = $user->getCurrency();
        $exchangeRates = $this->utils->cacheExchangeRates($currency);
        $viagogoAnalytics = $this->inventoryService->getViagogoAnalytics($user->getId(), $currency, $exchangeRates);
        $htmlNetAmount = $this->viagogoAnalyticsService->getHtmlNetAmount($viagogoAnalytics);
        $htmlTodayNetAmount = $this->viagogoAnalyticsService->getHtmlTodayNetAmount($viagogoAnalytics);

        return $this->render(
            'dashboard/home.html.twig',
            [
                'user' => $user,
                'bannerTitle' => new TranslatableMessage('This is your dashboard overview'),
                'bannerSubtitle' => new TranslatableMessage('Have a look at your analytics. Have you hopped on our Discord yet?'),
                'bannerBtnAction' => "https://discord.gg/4S4uzSrys7",
                'bannerBtnIcon' => '<i class="fa-brands fa-discord"></i>',
                'bannerBtnText' => new TranslatableMessage('Join Discord'),
                'viagogoAnalytics' => $viagogoAnalytics,
                'displayBanner' => true,
                'displayBannerBtn' => true,
                'htmlNetAmount' => $htmlNetAmount,
                'htmlTodayNetAmount' => $htmlTodayNetAmount,
                'totalSpentFormatted' => $this->utils->formatAmountArrayAsSymbol($viagogoAnalytics->getTotalSpent()),
            ]
        );
    }

    #[Route('/dashboard/no-membership', methods: ['GET'], name: 'dashboard_no_membership')]
    function showNoMembership(#[CurrentUser] ?User $user,)
    {
        if ($user && in_array('ROLE_MEMBER', $user->getRoles())) {
            return $this->redirectToRoute('dashboard');
        }

        $whopClientId = $this->getParameter('whop_client_id');
        $whopRedirectUri = $this->getParameter('whop_redirect_uri');

        return $this->render(
            'dashboard/no_membership.html.twig',
            [
                'user' => $user,
                'bannerTitle' => new TranslatableMessage('join_title', ['siteName' => $this->getParameter('site_name')]),
                'bannerSubtitle' => new TranslatableMessage("Looks like you don't have an active plan yet. Join today with the best offer ever!"),
                'bannerBtnIcon' => '<i class="fa-solid fa-key"></i>',
                'bannerBtnText' => new TranslatableMessage('Already have a pass?'),
                'bannerBtnId' => 'activateMembership',
                'displayBanner' => true,
                'displayBannerBtn' => true,
                'whopClientId' => $whopClientId,
                'whopRedirectUri' => $whopRedirectUri,
            ]
        );
    }
}
