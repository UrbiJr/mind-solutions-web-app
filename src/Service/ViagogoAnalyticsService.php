<?php

namespace App\Service;

use App\Entity\ViagogoAnalytics;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Exception;

class ViagogoAnalyticsService
{
    public function __construct(private readonly MemcachedAdapter $cache, private readonly Utils $utils)
    {
    }

    /**
     * Get the value of roi
     */
    public function calculateRoi(ViagogoAnalytics $analytics)
    {
        if ($analytics->getTotalSpent()["amount"] == 0) {
            throw new Exception("cannot divide by 0");
        }

        if ($analytics->getNetAmount()["currency"] === $analytics->getTotalSpent()["currency"]) {
            return (($analytics->getNetAmount()["amount"] - $analytics->getTotalSpent()["amount"]) / $analytics->getTotalSpent()["amount"]) * 100;
        } else {
            $currency = $analytics->getTotalSpent()["currency"];
            $exchangeRates = $this->utils->cacheExchangeRates($currency);
            // convert to use same currency
            $totalCostConverted = $this->utils->convertCurrency($analytics->getTotalSpent()["amount"], $exchangeRates, $analytics->getNetAmount()["currency"]);

            // now calculate roi
            return (($analytics->getNetAmount()["amount"] - $totalCostConverted) / $totalCostConverted) * 100;
        }
    }

    public function getHtmlNetAmount(ViagogoAnalytics $analytics)
    {
        try {
            $net = $analytics->getNetAmount()["amount"];
            return ($net >= 0) ? "<span class='text-success'>" . $this->utils->formatAmountArrayAsSymbol($analytics->getNetAmount()) . "</span>" : "<span class='text-danger'>" . $this->utils->formatAmountArrayAsSymbol($analytics->getNetAmount()) . "</span>";
        } catch (Exception $e) {
            return "<span>N/A</span>";
        }
    }

    public function getHtmlTodayNetAmount(ViagogoAnalytics $analytics)
    {
        try {
            $net = $analytics->getTodayNetAmount()["amount"];
            return ($net >= 0) ? "<span class='text-success'>" . $this->utils->formatAmountArrayAsSymbol($analytics->getTodayNetAmount()) . "</span>" : "<span class='text-danger'>" . $this->utils->formatAmountArrayAsSymbol($analytics->getTodayNetAmount()) . "</span>";
        } catch (Exception $e) {
            return "<span>N/A</span>";
        }
    }

    public function getHtmlRoi(ViagogoAnalytics $analytics)
    {
        try {
            $roi = $this->calculateRoi($analytics);
            return ($roi >= 0) ? "<span class='text-success'>" . sprintf("%.2F", $roi) . "%</span>" : "<span class='text-danger'>" . sprintf("%.2F", $roi) . "%</span>";
        } catch (Exception $e) {
            return "<span>N/A</span>";
        }
    }
}
