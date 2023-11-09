<?php

namespace App\Entity;

class ViagogoAnalytics
{
    private $userId;
    private $lastSale;
    private $quantitySold;
    private $quantityRemaining;
    private $totalSpent;
    private $todaySpent;
    private $netAmount;
    private $todayNetAmount;
    /**
     * @var TicketSale[]
     */
    private $sales;

    function __construct($userId, $lastSale, $quantitySold, $quantityRemaining, $totalSpent, $todaySpent, $netAmount, $todayNetAmount, $sales)
    {
        $this->userId = $userId;
        $this->lastSale = $lastSale;
        $this->quantitySold = $quantitySold;
        $this->quantityRemaining = $quantityRemaining;
        $this->totalSpent = isset($totalSpent) ? $totalSpent : ['amount' => 0, 'currency' => 'EUR'];
        $this->todaySpent = isset($todaySpent) ? $todaySpent : ['amount' => 0, 'currency' => 'EUR'];
        $this->netAmount = isset($netAmount) ? $netAmount : ['amount' => 0, 'currency' => 'EUR'];
        $this->todayNetAmount = isset($todayNetAmount) ? $todayNetAmount : ['amount' => 0, 'currency' => 'EUR'];
        $this->sales = isset($sales) && is_array($sales) ? $sales : array();
    }

    /**
     * Get the value of lastSale
     */
    public function getLastSale()
    {
        return $this->lastSale;
    }

    /**
     * Set the value of lastSale
     *
     * @return  self
     */
    public function setLastSale($lastSale)
    {
        $this->lastSale = $lastSale;

        return $this;
    }

    /**
     * Get the value of totalSpent
     */
    public function getTotalSpent()
    {
        return $this->totalSpent;
    }

    /**
     * Set the value of totalSpent
     *
     * @return  self
     */
    public function setTotalSpent($totalSpent)
    {
        $this->totalSpent = $totalSpent;

        return $this;
    }

    /**
     * Get the value of todaySpent
     */
    public function getTodaySpent()
    {
        return $this->todaySpent;
    }

    /**
     * Set the value of todaySpent
     *
     * @return  self
     */
    public function setTodaySpent($todaySpent)
    {
        $this->todaySpent = $todaySpent;

        return $this;
    }

    /**
     * Get the value of userId
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set the value of userId
     *
     * @return  self
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get the value of netAmount
     */
    public function getNetAmount()
    {
        return $this->netAmount;
    }

    /**
     * Set the value of netAmount
     *
     * @return  self
     */
    public function setNetAmount($netAmount)
    {
        $this->netAmount = $netAmount;

        return $this;
    }

    /**
     * Get the value of todayNetAmount
     */
    public function getTodayNetAmount()
    {
        return $this->todayNetAmount;
    }

    /**
     * Set the value of todayNetAmount
     *
     * @return  self
     */
    public function setTodayNetAmount($todayNetAmount)
    {
        $this->todayNetAmount = $todayNetAmount;

        return $this;
    }

    /**
     * Get the value of quantitySold
     */
    public function getQuantitySold()
    {
        return $this->quantitySold;
    }

    /**
     * Set the value of quantitySold
     *
     * @return  self
     */
    public function setQuantitySold($quantitySold)
    {
        $this->quantitySold = $quantitySold;

        return $this;
    }

    /**
     * Get the value of quantityRemaining
     */
    public function getQuantityRemaining()
    {
        return $this->quantityRemaining;
    }

    /**
     * Set the value of quantityRemaining
     *
     * @return  self
     */
    public function setQuantityRemaining($quantityRemaining)
    {
        $this->quantityRemaining = $quantityRemaining;

        return $this;
    }

    /**
     * Get the value of sales
     *
     * @return  TicketSale[]
     */
    public function getSales()
    {
        return $this->sales;
    }

    /**
     * Set the value of sales
     *
     * @param  TicketSale[]  $sales
     *
     * @return  self
     */
    public function setSales($sales)
    {
        $this->sales = isset($sales) && is_array($sales) ? $sales : $this->sales;

        return $this;
    }
}
