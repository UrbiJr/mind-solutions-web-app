<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\MassEditInventoryType;
use App\Form\Type\InventoryItemType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Translation\TranslatableMessage;

class InventoryController extends AbstractController
{
    #[Route('/inventory', name: 'inventory_show')]
    public function index(#[CurrentUser] ?User $user): Response
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');

        $addToInventoryForm = $this->createForm(InventoryItemType::class);
        $updateInventoryForm = $this->createForm(MassEditInventoryType::class);

        return $this->render(
            'inventory/overview.html.twig',
            [
                'user' => $user,
                'bannerTitle' => new TranslatableMessage('Inventory'),
                'bannerSubtitle' => new TranslatableMessage('Manage your inventory. Track your purchases and make your next move.'),
                'displayBanner' => true,
                'addToInventoryForm' => $addToInventoryForm,
                'updateInventoryForm' => $updateInventoryForm,
            ]
        );
    }
}
