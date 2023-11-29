<?php

namespace App\Service;

use App\Entity\InventoryItem;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

class InventoryService
{
    public function __construct(
        private readonly MemcachedAdapter $cache,
        private readonly Utils $utils,
        private readonly Firestore $firestore
    ) {
    }

    public function calculateRoi(InventoryItem $item)
    {
        if ($item->getTotalCost()["amount"] != 0) {
            if ($item->getTotalPayout()["currency"] === $item->getTotalCost()["currency"]) {
                return number_format((($item->getTotalPayout()["amount"] - $item->getTotalCost()["amount"]) / $item->getTotalCost()["amount"]) * 100, 2);
            } else {
                $currency = $item->getTotalCost()["currency"];
                $exchangeRates = $this->utils->cacheExchangeRates($currency);
                // convert to use same currency
                $totalCostConverted = $this->utils->convertCurrency($item->getTotalCost()["amount"], $exchangeRates, $item->getTotalPayout()["currency"]);
                // now calculate roi
                return number_format((($item->getTotalPayout()["amount"] - $totalCostConverted) / $totalCostConverted) * 100, 2);
            }
        } else {
            return "N/A";
        }
    }

    /**
     * Compare the inventory item with the viagogo listing
     * 
     * @param array $inventoryMap
     * @param $viagogoListing
     * @return bool
     */
    public function equalsViagogoListing($inventoryItem, $viagogoListing) {
        if (!isset($inventoryItem) || !isset($viagogoListing)) {
            return false;
        }

        $listingSeats = (isset($viagogoListing["Seats"])) ? explode("-", $viagogoListing["Seats"]) : array();
        if (sizeof($listingSeats) > 0) {
            $listingSeatFrom = $listingSeats[0];
            $listingSeatTo = $listingSeats[sizeof($listingSeats) - 1];
        } else {
            $listingSeatFrom = null;
            $listingSeatTo = null;
        }

        $itemSeatFrom = $inventoryItem->getSeatFrom();
        $itemSeatTo = $inventoryItem->getSeatTo();

        // compare item attributes with listing attributes (optional: add other attributes to compare)
        return $inventoryItem->getSection() === $viagogoListing["Section"] &&
            $itemSeatFrom === $listingSeatFrom &&
            $itemSeatTo === $listingSeatTo;
    }

    /**
     * Check if the listing is already in the inventory
     * 
     * @param array $inventoryMap
     * @param $viagogoListing
     */
    public function isListingOnInventory($inventory, $viagogoListing, $userId): ?InventoryItem
    {
        foreach ($inventory as $inventoryItem) {
            if ($inventoryItem->getViagogoEventId() === $viagogoListing['EventId']) {
                $equalsViagogoListing = $this->equalsViagogoListing($inventoryItem, $viagogoListing);
                if ($equalsViagogoListing) {
                    return $inventoryItem;
                }
            }
        }

        return null;
    }

    public function updateWithViagogoListing(InventoryItem $inventoryItem, $viagogoListing): InventoryItem
    {
        // update inventory item with viagogoListing data
        $inventoryItem->setStatus($viagogoListing["Status"]);
        $inventoryItem->setSaleEndDate($viagogoListing["SaleEndDate"]);
        $inventoryItem->setYourPricePerTicket(['amount' => $viagogoListing["PricePerTicket"]["Amount"], 'currency' => $viagogoListing["PricePerTicket"]["Currency"]]);
        $inventoryItem->setQuantity($viagogoListing["Quantity"]);
        $inventoryItem->setQuantityRemain($viagogoListing["QuantityRemain"]);
        $inventoryItem->setDateLastModified($viagogoListing["DateLastModified"]);
        $inventoryItem->setViagogoCategoryId($viagogoListing["CategoryId"]);
        return $inventoryItem;
    }
}
