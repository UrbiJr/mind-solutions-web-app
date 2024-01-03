<?php

namespace App\Service;

use App\Entity\InventoryItem;
use App\Entity\ViagogoAnalytics;
use App\Repository\InventoryItemRepository;
use DateTime;
use DateTimeInterface;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

class InventoryService
{
    public function __construct(
        private readonly MemcachedAdapter $cache,
        private readonly Utils $utils,
        private readonly InventoryItemRepository $inventoryItemRepository
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
                if (!isset($totalCostConverted)) {
                    return "N/A";
                }
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
    public function equalsViagogoListing($inventoryItem, $viagogoListing)
    {
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
    public function isListingOnInventory($inventory, $viagogoListing): ?InventoryItem
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
        if ($inventoryItem->getSection() !== null && str_contains(strtolower($inventoryItem->getSection()), 'floor')) {
            $inventoryItem->setFloorSeats($viagogoListing["Quantity"]);
        }
        $inventoryItem->setQuantityRemain($viagogoListing["QuantityRemain"]);
        $inventoryItem->setDateLastModified($viagogoListing["DateLastModified"]);
        $inventoryItem->setViagogoCategoryId($viagogoListing["CategoryId"]);
        return $inventoryItem;
    }

    function getViagogoAnalytics($userId, $currency, $exchangeRates): ViagogoAnalytics
    {
        $inventory = $this->inventoryItemRepository->getAllByUserId($userId);
        $sales = $this->inventoryItemRepository->getSalesByUserId($userId);
        $inventoryNotSold = $this->inventoryItemRepository->arrayDiff($inventory, $sales);

        $lastSale = null;
        $quantitySold = 0;
        $quantityRemaining = 0;
        $totalSpent = ['amount' => 0, 'currency' => $currency];
        $todaySpent = ['amount' => 0, 'currency' => $currency];
        $netAmount = ['amount' => 0, 'currency' => $currency];
        $todayNetAmount = ['amount' => 0, 'currency' => $currency];

        foreach ($inventoryNotSold as $inventoryItem) {
            $quantitySold += $inventoryItem->getQuantitySold();
            $quantityRemaining += $inventoryItem->getQuantityRemain();
            $totalSpent["amount"] += $this->utils->convertCurrency($inventoryItem->getTotalCost()["amount"], $exchangeRates, $inventoryItem->getTotalCost()["currency"]);
            if ($inventoryItem->getPurchaseDate() instanceof DateTimeInterface) {
                $date = new DateTime();
                if ($date->format('Y-m-d') === $inventoryItem->getPurchaseDate()->format('Y-m-d')) {
                    // purchase date corresponds to current day
                    $todaySpent["amount"] += $this->utils->convertCurrency($inventoryItem->getTotalCost()["amount"], $exchangeRates, $inventoryItem->getTotalCost()["currency"]);
                }
            }
        }

        foreach ($sales as $sale) {
            $totalSpent["amount"] += $this->utils->convertCurrency($sale->getTotalCost()["amount"], $exchangeRates, $sale->getTotalCost()["currency"]);
            $quantitySold += $sale->getPurchasedQuantity();
            $netAmount["amount"] += $this->utils->convertCurrency($sale->getTotalPayout()["amount"], $exchangeRates, $sale->getTotalPayout()["currency"]);
            if ($sale->getSaleDate() instanceof DateTimeInterface) {
                $date = new DateTime();
                if ($date->format('Y-m-d') === $sale->getSaleDate()->format('Y-m-d')) {
                    // purchase date corresponds to current day
                    $todayNetAmount["amount"] += $this->utils->convertCurrency($sale->getTotalPayout()["amount"], $exchangeRates, $sale->getTotalPayout()["currency"]);
                }
            }
        }

        $analytics = new ViagogoAnalytics($userId, $lastSale, $quantitySold, $quantityRemaining, $totalSpent, $todaySpent, $netAmount, $todayNetAmount, $sales);

        return $analytics;
    }
}
