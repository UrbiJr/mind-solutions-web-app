<?php

namespace App\Service;

use App\Entity\InventoryItem;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

class InventoryService
{
    public function __construct(private readonly MemcachedAdapter $cache, private readonly Utils $utils)
    {
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
}
