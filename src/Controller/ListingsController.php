<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\MassEditInventoryType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Translation\TranslatableMessage;

class ListingsController extends AbstractController
{
    #[Route('/listings', name: 'listings_show')]
    public function index(#[CurrentUser] ?User $user): Response
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');

        $updateInventoryForm = $this->createForm(MassEditInventoryType::class);

        return $this->render(
            'listings/overview.html.twig',
            [
                'user' => $user,
                'bannerTitle' => new TranslatableMessage('Active Listings'),
                'bannerSubtitle' => new TranslatableMessage('Manage what you listed on Viagogo.'),
                'displayBanner' => true,
                'updateInventoryForm' => $updateInventoryForm,
            ]
        );
    }
}
