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
use App\Repository\InventoryItemRepository;
use App\Repository\SectionListRepository;
use App\Service\InventoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Translation\TranslatableMessage;

class InventoryController extends AbstractController
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        protected readonly InventoryItemRepository $inventoryItemRepo,
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
    public function item_overview(#[CurrentUser] ?User $user, string $id, SectionListRepository $sectionListRepository, Request $request): Response
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');

        $item = $this->inventoryItemRepo->find($id);
        if (!$item) {
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

        $sectionList = $sectionListRepository->findOneByEventId($item->getViagogoEventId());
        if (isset($sectionList)) {
            $choices = $sectionList->getSections();
        } else {
            $choices = [];
        }
        $editListingForm = $this->createForm(ListingType::class, $item, ['sectionList' => $choices]);
        $editListingForm->get('yourPricePerTicketCurrency')->setData($item->getYourPricePerTicket()['currency']);
        $editListingForm->get('yourPricePerTicket')->setData($item->getYourPricePerTicket()['amount']);

        $editInventoryItemForm = $this->handleEditItemForm($item, $user, $request, $choices);

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

    private function handleEditItemForm(InventoryItem $item, User $user, Request $request, array $sections): FormInterface
    {
        $form = $this->createForm(InventoryItemType::class, $item, [
            'sectionList' => $sections,
            'individualTicketCost' => $item->getIndividualTicketCost(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // CHECK IF CUSTOM SECTION IS SUBMITTED
            if (key_exists('customSection', $form->all()) && $form->get('customSection')->getData() !== null) {
                $section = $form->get('customSection')->getData();
                $item->setSection($section);
            } // 'section' is already mapped so no need for an 'else' branch here: the attribute $section will get updated automatically

            // manually add non-mapped fields
            $individualTicketCostAmount = $form->get('individualTicketCost')->getData();
            $individualTicketCostCurrency = $form->get('individualTicketCostCurrency')->getData();
            $item->setIndividualTicketCost(['amount' => $individualTicketCostAmount, 'currency' => $individualTicketCostCurrency]);

            $this->inventoryItemRepo->edit($item);
            $this->addFlash('success', '💾 Successfully saved changes.');
        }

        return $form;
    }
}
