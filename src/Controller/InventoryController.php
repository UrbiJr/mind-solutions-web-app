<?php

namespace App\Controller;

use App\Entity\InventoryItem;
use App\Entity\User;
use App\Form\Type\MassEditInventoryType;
use App\Form\Type\InventoryItemType;
use App\Form\Type\ListingType;
use App\Form\Type\MarkItemAsListedType;
use App\Form\Type\MarkItemAsNotListedType;
use App\Form\Type\MarkItemAsSoldType;
use App\Service\Firestore;
use App\Service\InventoryService;
use Google\Cloud\Core\Exception\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Translation\TranslatableMessage;

class InventoryController extends AbstractController
{
    public function __construct(
        private readonly Firestore $firestore,
        private readonly InventoryService $inventoryService,
    ) {
    }

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

    #[Route('/inventory/{id}', name: 'inventory_item_show')]
    public function item_overview(#[CurrentUser] ?User $user, string $id): Response
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');

        try {
            $item = $this->firestore->get_inventory_item($id, $user->getId());
        } catch (NotFoundException) {
            return $this->redirectToRoute('error_404');
        }

        $statusHtml = '';
        switch ($item->getStatus()) {
            case InventoryItem::ITEM_NOT_LISTED:
                $statusHtml = '<span class="item-status text-warning"><b>Not Listed</b></span>';
                break;

            case InventoryItem::ITEM_LISTED:
                $statusHtml = '<span class="item-status text-primary"><b>Listed</b></span>';
                break;

            case InventoryItem::ITEM_SOLD:
                $statusHtml = '<span class="item-status text-success"><b>Sold</b></span>';
                break;

            default:
                # code...
                break;
        }

        $markItemAsListedForm = $this->createForm(MarkItemAsListedType::class, [
            'id' => $item->getId(),
            'quantity' => $item->getQuantity(),
            'quantityRemain' => $item->getQuantityRemain(),
            'status' => InventoryItem::ITEM_LISTED,
        ]);
        $markItemAsSoldForm = $this->createForm(MarkItemAsSoldType::class, [
            'id' => $item->getId(),
            'quantity' => $item->getQuantity(),
            'quantityRemain' => $item->getQuantityRemain(),
            'status' => InventoryItem::ITEM_SOLD,
        ]);
        $markItemAsNotListedForm = $this->createForm(MarkItemAsNotListedType::class, [
            'id' => $item->getId(),
            'quantity' => $item->getQuantity(),
            'platform' => $item->getPlatform(),
            'status' => InventoryItem::ITEM_NOT_LISTED,
        ]);
        $editListingForm = $this->createForm(ListingType::class, $item);
        $editListingForm->get('yourPricePerTicketCurrency')->setData($item->getYourPricePerTicket()['currency']);
        $editListingForm->get('yourPricePerTicket')->setData($item->getYourPricePerTicket()['amount']);

        $editInventoryItemForm = $this->createForm(InventoryItemType::class, $item);

        $itemRoi = $this->inventoryService->calculateRoi($item);

        return $this->render(
            'inventory/item_overview.html.twig',
            [
                'user' => $user,
                'bannerTitle' => $item->getName() . " - " . $item->getLocation() . " <h4>" . $item->getCity() . "</h4>",
                'bannerSubtitle' => '',
                'displayBanner' => true,
                'statusHtml' => $statusHtml,
                'item' => $item,
                'itemRoi' => $itemRoi,
                'markItemAsListedForm' => $markItemAsListedForm,
                'markItemAsSoldForm' => $markItemAsSoldForm,
                'markItemAsNotListedForm' => $markItemAsNotListedForm,
                'markItemAsNotListedForm' => $markItemAsNotListedForm,
                'editListingForm' => $editListingForm,
                'editInventoryItemForm' => $editInventoryItemForm,
            ]
        );
    }
}
