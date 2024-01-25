<?php

namespace App\Service;

use App\Controller\AJAXController;
use App\Entity\InventoryItem;
use App\Entity\User;
use App\Entity\ViagogoAnalytics;
use App\Repository\InventoryItemRepository;
use DateTime;
use DateTimeInterface;
use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

class InventoryService
{
    public function __construct(
        private readonly MemcachedAdapter $cache,
        private readonly Utils $utils,
        private readonly InventoryItemRepository $inventoryItemRepository,
        private readonly Client $client,
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

    public function calculateInventoryValue(User $user)
    {

        $exchangeRates = $this->utils->cacheExchangeRates($user->getCurrency());

        $items = $this->inventoryItemRepository->getAllByUserId($user->getId());
        $today = new DateTime();
        $floorPricesToFetch = array();
        $inventoryValue = 0;

        foreach ($items as $item) {
            $categoryId = $item->getViagogoCategoryId(); // Replace with the actual category ID
            $eventId = $item->getViagogoEventId(); // Replace with the actual event ID
            $section = $item->getSection(); // Replace with the actual section name
            $userCurrency = $user->getCurrency(); // Replace with the actual currency

            // if event has already happened OR if item has soldout
            if ($item->getEventDate() < $today || $item->getStatus() === InventoryItem::ITEM_SOLD) {
                // then use the net amount as item value
                $sold = ['amount' => $item->getPurchasedQuantity() * $item->getTotalPayout()["amount"], 'currency' => $item->getTotalPayout()["currency"]];
                if (strtoupper($userCurrency) === strtoupper($sold["currency"])) {
                    $inventoryItemValue = $sold['amount'];
                } else {
                    $inventoryItemValue = $this->utils->convertCurrency(floatval($sold["amount"]), $exchangeRates, $sold["currency"]);
                }
                $inventoryValue += $inventoryItemValue;
            } else {
                // otherwise use section Floor Price * quantity remaining
                $cacheItem = $this->cache->getItem('viagogoSectionFloorPrice_' . str_replace(' ', '', $section) . $eventId);
                $floorPrice = $cacheItem->get();

                // if floor price is in cache...
                if ($cacheItem->isHit() && !is_bool($floorPrice)) {
                    if (strtoupper($userCurrency) === strtoupper($floorPrice["currency"])) {
                        $inventoryItemValue = $floorPrice["floorPrice"] * $item->getQuantityRemain();
                    } else {
                        $floorPriceConverted = $this->utils->convertCurrency(floatval($floorPrice["floorPrice"]), $exchangeRates, $floorPrice["currency"]);
                        $inventoryItemValue = $floorPriceConverted * $item->getQuantityRemain();
                    }
                    $inventoryValue += $inventoryItemValue;
                } else {
                    // otherwise, fetch it later
                    $floorPricesToFetch[$item->getId()] = array("itemId" => $item->getId(), "eventId" => $eventId, "categoryId" => $categoryId, "section" => $section, "quantityRemain" => $item->getQuantityRemain());
                }
            }
        }

        if (sizeof($floorPricesToFetch) > 0) {
            /* fetch floor prices */
            $apiEndpoint = 'https://api.mindsolutions.app/viagogo/sections/floor-price/all'; // Replace with the URL of your PHP script
            $jwtToken = $this->utils->generateToken(AJAXController::JWT_EXPIRY_IN_SECONDS);

            $data = [
                'items' => array_values($floorPricesToFetch),
                'currency' => $userCurrency,
            ];

            // Convert the POST data array to JSON
            $postDataJson = json_encode($data);

            // Make the POST request to the API using Guzzle
            try {
                $response = $this->client->post($apiEndpoint, [
                    'headers' => [
                        'Authorization' => "Bearer $jwtToken",
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $postDataJson,
                ]);

                // Get the response body
                $responseBody = $response->getBody()->getContents();

                $floorPrices = json_decode($responseBody)['floorPrices'];
                if (isset($floorPrices) && is_array($floorPrices)) {
                    foreach ($floorPrices as $floorPrice) {
                        $floorPrice = (array) $floorPrice;
                        if (isset($floorPricesToFetch[$floorPrice["itemId"]])) {
                            $inventoryValue += $floorPrice['floorPrice'] * $floorPricesToFetch[$floorPrice["itemId"]]['quantityRemain'];
                        }
                        // Store section foor price in cache for 10 minutes (adjust TTL as needed)
                        $cacheItem = $this->cache->getItem('viagogoSectionFloorPrice_' . str_replace(' ', '', $floorPrice['section']) . $floorPrice['eventId']);
                        $cacheItem->set($floorPrice);
                        $cacheItem->expiresAfter(600); // 10 minutes
                        // save the cache item
                        $this->cache->save($cacheItem);
                    }
                }
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                // Handle exceptions or errors here
                // You can access the error response using $e->getResponse()
                // Example: $errorResponse = $e->getResponse()->getBody()->getContents();
            }
        }

        return $inventoryValue;
    }
}
