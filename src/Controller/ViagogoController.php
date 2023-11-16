<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Translation\TranslatableMessage;

class ViagogoController extends AbstractController
{
    function __construct(
        private readonly MemcachedAdapter $cache,
    ) {
    }

    #[Route('/viagogo', name: 'viagogo_connection_show')]
    public function index(#[CurrentUser] ?User $user): Response
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');

        $cacheItem = $this->cache->getItem("viagogoUser_" . $user->getId());
        if ($cacheItem->isHit()) {
            return $this->render(
                'viagogo/connected.html.twig',
                [
                    'user' => $user,
                    'viagogoUser' => $cacheItem->get(),
                    'bannerTitle' => 'Viagogo',
                    'bannerSubtitle' => new TranslatableMessage('Connect to to your Viagogo account.'),
                    'displayBanner' => true,
                ]
            );
        } else {
            return $this->render(
                'viagogo/login.html.twig',
                [
                    'user' => $user,
                    'bannerTitle' => 'Viagogo',
                    'bannerSubtitle' => new TranslatableMessage('Connect to to your Viagogo account.'),
                    'displayBanner' => true,
                ]
            );
        }
    }

    #[Route('/viagogo/reset', name: 'viagogo_reset_session')]
    public function viagogo_reset(#[CurrentUser] ?User $user): Response
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');

        // Delete item from cache
        $this->cache->deleteItem("viagogoUser_" . $user->getId());

        $this->addFlash('notice', "Viagogo session reset. You may now want to login again. <a class='btn btn-soft-light' href='{$this->generateUrl('viagogo_connection_show')}'>Login</a>");

        return $this->render(
            'viagogo/login.html.twig',
            [
                'user' => $user,
                'bannerTitle' => 'Viagogo',
                'bannerSubtitle' => new TranslatableMessage('Connect to to your Viagogo account.'),
                'displayBanner' => true,
            ]
        );
    }
}
